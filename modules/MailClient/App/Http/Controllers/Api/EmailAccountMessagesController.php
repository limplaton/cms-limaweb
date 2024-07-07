<?php
 

namespace Modules\MailClient\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\App\Common\OAuth\EmptyRefreshTokenException;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\MailClient\App\Client\Compose\AbstractComposer;
use Modules\MailClient\App\Client\Compose\Message;
use Modules\MailClient\App\Client\Compose\MessageForward;
use Modules\MailClient\App\Client\Compose\MessageReply;
use Modules\MailClient\App\Client\Exceptions\ConnectionErrorException;
use Modules\MailClient\App\Client\Exceptions\FolderNotFoundException;
use Modules\MailClient\App\Client\Exceptions\MessageNotFoundException;
use Modules\MailClient\App\Client\Exceptions\RateLimitExceededException;
use Modules\MailClient\App\Client\Exceptions\ServiceUnavailableException;
use Modules\MailClient\App\Concerns\InteractsWithEmailMessageAssociations;
use Modules\MailClient\App\Criteria\EmailAccountMessageCriteria;
use Modules\MailClient\App\Http\Requests\MessageRequest;
use Modules\MailClient\App\Http\Resources\EmailAccountMessageResource;
use Modules\MailClient\App\Models\EmailAccount;
use Modules\MailClient\App\Models\EmailAccountMessage;
use Modules\MailClient\App\Services\EmailAccountMessageSyncService;

class EmailAccountMessagesController extends ApiController
{
    use InteractsWithEmailMessageAssociations;

    /**
     * Get messages for account folder.
     */
    public function index(string $accountId, string $folderId, Request $request): JsonResponse
    {
        $this->authorize('view', EmailAccount::findOrFail($accountId));

        $messages = EmailAccountMessage::withCommon()
            ->criteria(new EmailAccountMessageCriteria($accountId, $folderId))
            ->paginate($request->integer('per_page') ?: null);

        return $this->response(
            EmailAccountMessageResource::collection($messages)
        );
    }

    /**
     * Send new message.
     */
    public function create(string $accountId, MessageRequest $request): JsonResponse
    {
        $this->authorize('view', $account = EmailAccount::findOrFail($accountId));

        if ($request->scheduled_at) {
            $request->scheduler($account, 'send')->schedule($request->scheduled_at);

            return $this->response([], JsonResponse::HTTP_CREATED);
        }

        $composer = new Message(
            $account->createClient(),
            $account->sentFolder->identifier()
        );

        return $this->sendMessage($composer, $accountId, $request);
    }

    /**
     * Reply to a message.
     */
    public function reply(string $id, MessageRequest $request): JsonResponse
    {
        $message = EmailAccountMessage::with(['account', 'folders.account'])->findOrFail($id);

        $this->authorize('view', $message->account);

        if ($request->scheduled_at) {
            $request->scheduler($message->account, 'reply', $message->id)->schedule($request->scheduled_at);

            return $this->response([], JsonResponse::HTTP_CREATED);
        }

        $composer = new MessageReply(
            $message->account->createClient(),
            $message->remote_id,
            $message->folders->first()->identifier(),
            $message->account->sentFolder->identifier()
        );

        return $this->sendMessage($composer, $message->email_account_id, $request);
    }

    /**
     * Forward a message.
     */
    public function forward(string $id, MessageRequest $request): JsonResponse
    {
        $message = EmailAccountMessage::with(['account', 'folders.account'])->findOrFail($id);

        $this->authorize('view', $message->account);

        $attachments = $message->attachments->find($request->input('forward_attachments', []));

        if ($request->scheduled_at) {
            $request
                ->scheduler($message->account, 'forward', $message->id)
                ->attachments($attachments->all())
                ->schedule($request->scheduled_at);

            return $this->response([], JsonResponse::HTTP_CREATED);
        }

        $composer = new MessageForward(
            $message->account->createClient(),
            $message->remote_id,
            $message->folders->first()->identifier(),
            $message->account->sentFolder->identifier()
        );

        // Add the original selected message attachments
        foreach ($attachments as $attachment) {
            $composer->attachFromStorageDisk(
                $attachment->disk,
                $attachment->getDiskPath(),
                $attachment->basename
            );
        }

        return $this->sendMessage($composer, $message->email_account_id, $request);
    }

    /**
     * Get email account message.
     */
    public function show(string $folderId, string $id, Request $request): JsonResponse
    {
        $message = EmailAccountMessage::withCommon()->withCountAssociations()->findOrFail($id);

        $this->authorize('view', $message->account);

        if (! $request->boolean('silent')) {
            try {
                $message->markAsRead($folderId);
            } catch (MessageNotFoundException) {
                return $this->response(['message' => 'The message does not exist on remote server.'], 409);
            } catch (FolderNotFoundException) {
                return $this->response(['message' => 'The folder the message belongs to does not exist on remote server.'], 409);
            } catch (RateLimitExceededException|ServiceUnavailableException $e) {
                if ($e->retryAfter()) {
                    $message->account->holdSyncUntil($e->retryAfter());
                }
            } catch (EmptyRefreshTokenException) {
                // Probably here the account is disabled and no other actions are needed
            }
        }

        // Reload the account and all it's relationship so the unread_count of the folders
        // is updated  in case the message was marked as read above.
        $message->load(['account' => function ($query) {
            $query->withCommon();
        }]);

        return $this->response((new EmailAccountMessageResource($message))->withActions(
            $message::resource()->resolveActions(
                app(ResourceRequest::class)->setResource($message::resource()->name())
            )
        ));
    }

    /**
     * Delete message from storage.
     */
    public function destroy(string $messageId, EmailAccountMessageSyncService $service): JsonResponse
    {
        $message = EmailAccountMessage::findOrFail($messageId);

        $this->authorize('view', $message->account);

        $service->delete($message->id);

        return $this->response('', JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Mark the given message as read.
     */
    public function read(string $messageId): JsonResponse
    {
        $message = EmailAccountMessage::withCommon()->withCountAssociations()->find($messageId);

        if (! $message->account->isSyncOnHold()) {
            $message->markAsRead();
        }

        return $this->response(new EmailAccountMessageResource(
            $message
        ));
    }

    /**
     * Mark the given message as unread.
     */
    public function unread(string $messageId): JsonResponse
    {
        $message = EmailAccountMessage::withCommon()->withCountAssociations()->find($messageId);

        if (! $message->account->isSyncOnHold()) {
            $message->markAsUnread();
        }

        return $this->response(new EmailAccountMessageResource(
            $message
        ));
    }

    /**
     * Send the message.
     *
     * @param  int|string  $acountId
     */
    protected function sendMessage(AbstractComposer $composer, $accountId, MessageRequest $request): JsonResponse
    {
        $this->addComposerAssociationsHeaders($composer, $request->associations());
        $this->addPendingAttachments($composer, $request);

        try {
            $composer->subject($request->subject)
                ->to($request->to)
                ->bcc($request->bcc)
                ->cc($request->cc)
                ->htmlBody($request->message)
                ->withTrackers();

            $message = $composer->send();
        } catch (ConnectionErrorException|MessageNotFoundException|FolderNotFoundException $e) {
            return $this->response(['message' => $e->getMessage()], 409);
        } catch (RateLimitExceededException|ServiceUnavailableException $e) {
            if ($e->retryAfter()) {
                EmailAccount::find($accountId)->holdSyncUntil($e->retryAfter());
            }

            return $this->response(['message' => 'Rate limit exceeded or service unavailable, try again later.'], 409);
        } catch (\Exception $e) {
            return $this->response(['message' => $e->getMessage()], 500);
        }

        if (! is_null($message)) {
            $dbMessage = (new EmailAccountMessageSyncService)->create(
                $accountId,
                $message,
                $request->associations()
            );

            return $this->toMessageResponse($dbMessage->id, JsonResponse::HTTP_OK);
        }

        return $this->response([], JsonResponse::HTTP_ACCEPTED);
    }

    /**
     * Add the attachments (if any) to the message composer.
     */
    protected function addPendingAttachments(AbstractComposer $composer, MessageRequest $request): void
    {
        foreach ($request->pendingAttachments() as $pendingMedia) {
            $composer->attachFromStorageDisk(
                $pendingMedia->attachment->disk,
                $pendingMedia->attachment->getDiskPath(),
                $pendingMedia->attachment->basename
            );
        }
    }

    protected function toMessageResponse($messageId, int $status = 200): JsonResponse
    {
        $jsonResource = new EmailAccountMessageResource(
            EmailAccountMessage::withCommon()->withCountAssociations()->find($messageId)
        );

        return $this->response($jsonResource->withActions(
            EmailAccountMessage::resource()->resolveActions(
                app(ResourceRequest::class)->setResource(EmailAccountMessage::resource()->name())
            )
        ), $status);
    }
}
