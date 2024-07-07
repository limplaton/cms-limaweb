<?php
 

namespace Modules\MailClient\App\Services;

use Exception;
use Illuminate\Database\Eloquent\Collection as DatabaseCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use MediaUploader;
use Modules\Core\App\Common\Mail\ContentDecoder;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Resource\AssociatesResources;
use Modules\MailClient\App\Client\AbstractMessage;
use Modules\MailClient\App\Client\Contracts\AttachmentInterface;
use Modules\MailClient\App\Concerns\InteractsWithEmailMessageAssociations;
use Modules\MailClient\App\Events\EmailAccountMessageCreated;
use Modules\MailClient\App\Models\EmailAccount;
use Modules\MailClient\App\Models\EmailAccountMessage;
use PDOException;
use Plank\Mediable\Exceptions\MediaUploadException;

class EmailAccountMessageSyncService
{
    use AssociatesResources,
        InteractsWithEmailMessageAssociations;

    /**
     * Message addresses headers and relations.
     */
    protected array $addresses = ['from', 'to', 'cc', 'bcc', 'replyTo', 'sender'];

    /**
     * Cache account folders
     *
     * When creating a lot messages we don't want
     *
     * thousands of queries to be executed
     */
    protected array $cachedAccountFolders = [];

    /**
     * Get messages for account
     */
    public function create(int $accountId, AbstractMessage $message, ?array $associations = null)
    {
        $data = $message->toArray();

        $attributes = array_merge($data, [
            'email_account_id' => $accountId,
            'is_sent_via_app' => $message->isSentFromApplication(),
            'hash' => $message->getHeader('x--hash')?->getValue(),
        ]);

        if (is_string($attributes['subject'])) {
            $attributes['subject'] = $this->ensureSubjectDoesNotExceedsMaxCharacters($attributes['subject']) ?: null;
        }

        if (is_string($attributes['html_body'])) {
            $attributes['html_body'] = trim($attributes['html_body']);
        }

        if (is_string($attributes['text_body'])) {
            $attributes['text_body'] = trim($attributes['text_body']);
        }

        try {
            $dbMessage = EmailAccountMessage::create($attributes);
        } catch (PDOException $e) {
            // In most cases this may happen when the message has invalid subject or body
            // Confirmed with subject that contains binary string
            // https://stackoverflow.com/questions/1734005/in-php-what-is-a-binary-string-bxxxx
            if ($this->isPDOExceptionInvalidSubjectString($e)) {
                $dbMessage = EmailAccountMessage::create(array_merge($attributes, [
                    'subject' => '[Invalid Subject]',
                ]));
            } elseif ($this->isPDOExceptionInvalidMessageString($e)) {
                $dbMessage = EmailAccountMessage::create(array_merge($attributes, [
                    'html_body' => '[Invalid Message]',
                    'text_body' => $attributes['text_body'] ? '[Invalid Message]' : null,
                ]));
            } else {
                throw $e;
            }
        }

        $this->persistAddresses($data, $dbMessage);
        $this->persistHeaders($message, $dbMessage);

        $dbMessage->folders()->sync(
            $this->determineMessageDatabaseFolders($message, $dbMessage)
        );

        $this->handleAttachments($dbMessage, $message);

        // When associations are passed manually
        // this means that the user can manually associate the message
        // to resources, in this case, we use the user associations
        // after that for each reply from the client for this messages, the user
        // selected associations are used.
        if ($associations) {
            $this->attachAssociations('emails', $dbMessage->getKey(), $associations);
        } else {
            if ($dbMessage->isReply()) {
                $this->syncAssociationsWhenReply($dbMessage, $message);
            } else {
                // If the message is queued, we need to fetch the associations from
                // the headers and sync with the actual associations
                $this->syncAssociationsViaMessageHeaders($dbMessage, $message);
            }
        }

        event(new EmailAccountMessageCreated($dbMessage, $message));

        return $dbMessage;
    }

    /**
     * Update a message for a given account
     *
     * NOTE: This functions does not sync attachments
     */
    public function update(AbstractMessage $message, EmailAccountMessage|int $dbMessage): EmailAccountMessage
    {
        $attributes = $message->toArray();

        $dbMessage = is_int($dbMessage) ? EmailAccountMessage::find($dbMessage) : $dbMessage;

        if (is_string($attributes['subject'])) {
            $attributes['subject'] = $this->ensureSubjectDoesNotExceedsMaxCharacters($attributes['subject']) ?: null;
        }

        if (is_string($attributes['html_body'])) {
            $attributes['html_body'] = trim($attributes['html_body']);
        }

        if (is_string($attributes['text_body'])) {
            $attributes['text_body'] = trim($attributes['text_body']);
        }

        try {
            $dbMessage->fill($attributes)->save();
        } catch (PDOException $e) {
            // In most cases this may happen when the message has invalid subject or body
            // Confirmed with subject that contains binary string
            // https://stackoverflow.com/questions/1734005/in-php-what-is-a-binary-string-bxxxx
            if ($this->isPDOExceptionInvalidSubjectString($e)) {
                $dbMessage->fill(['subject' => '[Invalid Subject]'])->save();
            } elseif ($this->isPDOExceptionInvalidMessageString($e)) {
                $dbMessage->fill([
                    'html_body' => '[Invalid Message]',
                    'text_body' => '[Invalid Message]',
                ])->save();
            } else {
                throw $e;
            }
        }

        $this->persistAddresses($attributes, $dbMessage);
        $this->persistHeaders($message, $dbMessage);
        $this->replaceBodyInlineAttachments($dbMessage, $message);

        $dbMessage->folders()->sync(
            $this->determineMessageDatabaseFolders($message, $dbMessage)
        );

        return $dbMessage;
    }

    /**
     * Create the message addresses
     */
    protected function persistAddresses(array $data, EmailAccountMessage $message): void
    {
        // Delete the existing addresses
        // Below we will re-create them
        $message->addresses()->delete();

        foreach ($this->addresses as $type) {
            if (is_null($data[$type])) {
                continue;
            }

            $this->createAddresses($message, $data[$type], $type);
        }
    }

    /**
     * Delete account message(s)
     *
     * @param  int|\Illuminate\Database\Eloquent\Collection  $message
     * @param  null|int  $fromFolderId
     */
    public function delete($message, $fromFolderId = null): void
    {
        $service = new EmailAccountMessageService();

        $eagerLoad = ['folders', 'account', 'account.trashFolder'];

        $allAccounts = EmailAccount::with('trashFolder')->get();

        $messages = is_numeric($message) ?
            new DatabaseCollection([EmailAccountMessage::with($eagerLoad)->find($message)]) :
            $message->loadMissing($eagerLoad);

        $queue = $messages->mapToGroups(function ($message) {
            // When message is in the trash folder, we will parmanently delete
            // this message from the remote server
            if ($message->folders->find($message->account->trashFolder)) {
                return ['delete' => $message];
            }

            return ['move' => $message];
        });

        if (isset($queue['move'])) {
            $queue['move']->groupBy('email_account_id')
                ->each(function ($messages, $accountId) use ($service, $fromFolderId, $allAccounts) {
                    $service->batchMoveTo(
                        $messages,
                        $allAccounts->find($accountId)->trashFolder,
                        $fromFolderId
                    );
                });
        }

        if (isset($queue['delete'])) {
            $service->batchDelete($queue['delete']);
        }
    }

    /**
     * Create message addresses
     *
     * @param  \Modules\Core\App\Common\Mail\Headers\AddressHeader  $addresses
     */
    protected function createAddresses(EmailAccountMessage $message, $addresses, string $type): void
    {
        foreach ($addresses->getAll() as $address) {
            $message->addresses()->create(array_merge($address, [
                'address_type' => $type,
            ]));
        }
    }

    /**
     * Persist the message header in database
     *
     * @param \Modules\MailClient\App\Client\Contracts\MessageInterface
     */
    protected function persistHeaders($message, EmailAccountMessage $dbMessage): void
    {
        if ($inReplyTO = $message->getHeader('in-reply-to')) {
            $dbMessage->headers()->updateOrCreate([
                'name' => 'in-reply-to',
            ], [
                'name' => 'in-reply-to',
                'value' => $inReplyTO->getValue(),
                'header_type' => $inReplyTO::class,
            ]);
        }

        if ($references = $message->getHeader('references')) {
            $dbMessage->headers()->updateOrCreate([
                'name' => 'references',
            ], [
                'name' => 'references',
                'value' => implode(', ', $references->getIds()),
                'header_type' => $references::class,
            ]);
        }
    }

    /**
     * Determine the message database folders
     * based on the message folder ID's
     *
     * @param  \Modules\MailClient\App\Client\Contracts\MessageInterface  $imapMessage
     * @param  \Modules\MailClient\App\Models\EmailAccountMessage  $dbMessage
     */
    protected function determineMessageDatabaseFolders($imapMessage, $dbMessage): array
    {
        if (isset($this->cachedAccountFolders[$dbMessage->email_account_id])) {
            $folders = $this->cachedAccountFolders[$dbMessage->email_account_id];
        } else {
            $folders = $this->cachedAccountFolders[$dbMessage->email_account_id] = $dbMessage->account->folders;
            // For identifier looping in EmailAccountFolderCollection, avoids lazy loading protection
            $folders->loadMissing('account');
        }

        return $folders->findWhereIdentifierIn($imapMessage->getFolders())->pluck('id')->all();
    }

    /**
     * Save the message attachments
     *
     * @param  \Modules\MailClient\App\Models\EmailAccountMessage  $message
     * @param  \Modules\MailClient\App\Client\Contracts\MessageInterface  $imapMessage
     */
    protected function handleAttachments($dbMessage, $imapMessage): array
    {
        // Store embedded attachments with embedded-attachments tag
        // We will cast as embedded/inline attachments only the attachments which
        // exists in the message body with src="cid_CONTENT_ID"
        $embeddedAttachments = $this->replaceBodyInlineAttachments($dbMessage, $imapMessage);

        // Remove the embedded attachments as they are stored with different tag
        $attachments = $imapMessage->getAttachments()
            ->reject(
                fn ($attachment, $key) => in_array($key, $embeddedAttachments)
            )->values();

        // Store non-embedded attachments
        return $this->storeAttachments($attachments, $dbMessage, EmailAccountMessage::ATTACHMENTS_MEDIA_TAG);
    }

    /**
     * Replace the message body inline attachments with the actual media links
     *
     * @param \Modules\MailClient\App\Models\EmailAccountMessage
     * @param  \Modules\MailClient\App\Client\Contracts\MessageInterface  $imapMessage
     */
    protected function replaceBodyInlineAttachments($dbMessage, $imapMessage): array
    {
        $embeddedAttachmentsKeys = [];

        // We will provide a closure to the getPreviewBody method
        // to provide a custom content for the replace
        $replaceCallback = function ($file) use ($dbMessage, $imapMessage, &$embeddedAttachmentsKeys) {
            foreach ($imapMessage->getAttachments() as $key => $attachment) {
                if ($attachment->getContentId() === $file->getContentId()) {
                    // Check if the attachment with this content-id is already stored
                    // if yes, we will return the same media preview url
                    // Useful e.q. on update when the message already exists and
                    // we are trying to update it
                    $media = $dbMessage->inlineAttachments->first(function ($inlineMedia) use ($file) {
                        return $inlineMedia->getMeta('content-id') === $file->getContentId();
                    });

                    // When no media with this content-id found, we will create
                    // the media as embedded attachment and will set the meta content-id
                    if (
                        is_null($media) && $media = $this->storeAttachments($attachment, $dbMessage, EmailAccountMessage::EMBEDDED_ATTACHMENTS_MEDIA_TAG)[0] ?? null
                    ) {
                        $media->setMeta('content-id', $file->getContentId());
                    }

                    if ($media) {
                        $embeddedAttachmentsKeys[] = $key;

                        return $media->previewPath();
                    }
                }
            }
        };

        $dbMessage->html_body = $imapMessage->getPreviewBody($replaceCallback);
        $dbMessage->save();

        return $embeddedAttachmentsKeys;
    }

    /**
     * Store message attachments
     *
     * @param  \Iluminate\Support\Collection|\Modules\MailClient\App\Client\Contracts\AttachmentInterface  $attachments
     * @param  \Modules\MailClient\App\Models\EmailAccountMessage  $message
     * @param  string  $tag
     */
    protected function storeAttachments($attachments, $message, $tag): array
    {
        if ($attachments instanceof AttachmentInterface) {
            $attachments = [$attachments];
        }

        $storedMedia = [];
        $allowedExtensions = Innoclapps::allowedUploadExtensions();

        foreach ($attachments as $attachment) {
            $tmpFile = tmpfile();

            fwrite(
                $tmpFile,
                ContentDecoder::decode($attachment->getContent(), $attachment->getEncoding())
            );

            try {
                $media = MediaUploader::fromSource($tmpFile)
                    ->toDirectory($message->getMediaDirectory())
                    ->onDuplicateIncrement()
                    ->useFilename($filename = pathinfo($attachment->getFileName(), PATHINFO_FILENAME))
                    // Allow any extension
                    ->setAllowedExtensions(array_unique(
                        array_merge($allowedExtensions, [pathinfo($attachment->getFileName(), PATHINFO_EXTENSION)])
                    ))
                    ->upload();

                $message->attachMedia($media, $tag);

                $storedMedia[] = $media;
            } catch (MediaUploadException $e) {
                Log::debug(
                    sprintf(
                        'Failed to store mail message [ID: %s] attachment, filename: %s, exception message: %s',
                        $message->getKey(),
                        $filename,
                        $e->getMessage()
                    ),
                );

                continue;
            } finally {
                // If the media package did not closed the file, close it
                // As per the tests, it looks like the package closes the tmpfile
                if (is_resource($tmpFile)) {
                    fclose($tmpFile);
                }
            }
        }

        return $storedMedia;
    }

    /**
     * Associate the message if it's reply to
     * the original message the reply is performed to
     *
     * @param \Modules\MailClient\App\Models\EmailAccountMessage
     * @param \Modules\MailClient\App\Client\Contracts\MessageInterface
     */
    protected function syncAssociationsWhenReply($dbMessage, $remoteMessage): bool
    {
        // If the message is sent from the application,
        // we will use the headers to associate the selected
        // associations, otherwise, we will use the dependent message
        // If this method is hit, this means that the message was queued for
        // sync and was not inserted in database when the user click send, hence
        // the associations were not saved in database
        if ($remoteMessage->isSentFromApplication()) {
            $this->syncAssociationsViaMessageHeaders($dbMessage, $remoteMessage);

            return true;
        }

        $inReplyTo = $dbMessage->headers->firstWhere('name', 'in-reply-to');
        $references = $dbMessage->headers->firstWhere('name', 'references');

        // First check the in-reply to header as it's the most applicable header
        if ($inReplyTo) {
            $inReplyToMessageId = $inReplyTo->mapped->getValue();
        } elseif ($references) {
            // If in-reply-to header is not set, let's check the references
            // and get the last reference, probably the mail client set the message that replied as reference
            $referencesIds = $references->mapped->getIds();

            if (count($referencesIds) === 0 || empty($references->value)) {
                return false;
            }

            $inReplyToMessageId = $referencesIds[array_key_last($referencesIds)];
        } else {
            return false;
        }

        $inReplyToDbMessage = EmailAccountMessage::query()
            ->whereFullText('message_id', $inReplyToMessageId)
            ->where('email_account_id', $dbMessage->email_account_id)
            ->first();

        if ($inReplyToDbMessage) {
            foreach ($inReplyToDbMessage->loadAssociations()->associateableRelations() as $relation) {
                $dbMessage->{$relation}()->sync($inReplyToDbMessage->{$relation}->modelKeys());
            }

            return true;
        }

        return false;
    }

    protected function ensureSubjectDoesNotExceedsMaxCharacters(?string $subject = null): ?string
    {
        if (Str::length($subject ?? '') > 191) {
            $subject = Str::substr($subject, 0, 188).'...';
        }

        return $subject;
    }

    protected function isPDOExceptionIncorrectStringValue(PDOException|Exception $e): bool
    {
        return str_contains($e->getMessage(), 'Incorrect string value');
    }

    protected function isPDOExceptionInvalidSubjectString(PDOException|Exception $e): bool
    {
        if (! str_contains($e->getMessage(), '`subject` at row 1')) {
            return false;
        }

        return $this->isPDOExceptionIncorrectStringValue($e);
    }

    protected function isPDOExceptionInvalidMessageString(PDOException|Exception $e): bool
    {
        if (! Str::contains($e->getMessage(), ['`html_body` at row 1', '`text_body` at row 1'])) {
            return false;
        }

        return $this->isPDOExceptionIncorrectStringValue($e);
    }
}
