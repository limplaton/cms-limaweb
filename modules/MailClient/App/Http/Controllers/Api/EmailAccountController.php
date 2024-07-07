<?php
 

namespace Modules\MailClient\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\MailClient\App\Criteria\EmailAccountsForUserCriteria;
use Modules\MailClient\App\Http\Requests\EmailAccountRequest;
use Modules\MailClient\App\Http\Resources\EmailAccountResource;
use Modules\MailClient\App\Models\EmailAccount;
use Modules\MailClient\App\Services\EmailAccountService;

class EmailAccountController extends ApiController
{
    /**
     * Get all email accounts the user can access.
     */
    public function index(): JsonResponse
    {
        $accounts = EmailAccount::withCommon()
            ->criteria(EmailAccountsForUserCriteria::class)
            ->get();

        return $this->response(
            EmailAccountResource::collection($accounts)
        );
    }

    /**
     * Display email account.
     */
    public function show(string $id): JsonResponse
    {
        $account = EmailAccount::withCommon()->findOrFail($id);

        $this->authorize('view', $account);

        return $this->response(new EmailAccountResource($account));
    }

    /**
     * Store a newly created email account in storage.
     */
    public function store(EmailAccountRequest $request, EmailAccountService $service): JsonResponse
    {
        $model = $service->create($request->all());

        $account = EmailAccount::withCommon()->find($model->id);

        $account->wasRecentlyCreated = true;

        return $this->response(
            new EmailAccountResource($account),
            JsonResponse::HTTP_CREATED
        );
    }

    /**
     * Update the specified account in storage.
     */
    public function update(string $id, EmailAccountRequest $request, EmailAccountService $service): JsonResponse
    {
        $this->authorize('update', $account = EmailAccount::find($id));

        // The user is not allowed to update these fields after creation
        $except = ['email', 'connection_type', 'user_id', 'initial_sync_from'];

        $service->update($account, $request->except($except));

        return $this->response(
            new EmailAccountResource(EmailAccount::withCommon()->find($account->id))
        );
    }

    /**
     * Remove the specified account from storage.
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        $this->authorize('delete', $account = EmailAccount::findOrFail($id));

        $account->delete();

        return $this->response([
            'unread_count' => EmailAccount::countUnreadMessagesForUser($request->user()),
        ]);
    }

    /**
     * Get all shared accounts unread messages.
     */
    public function unread(Request $request): int
    {
        return EmailAccount::countUnreadMessagesForUser($request->user());
    }
}
