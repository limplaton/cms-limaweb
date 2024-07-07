<?php
 

namespace Modules\MailClient\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Core\App\Common\Synchronization\SyncState;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\MailClient\App\Http\Resources\EmailAccountResource;
use Modules\MailClient\App\Models\EmailAccount;

class EmailAccountSyncStateController extends ApiController
{
    /**
     * Enable synchronization for the given email account.
     */
    public function enable(string $id): JsonResponse
    {
        $account = EmailAccount::withCommon()->findOrFail($id);

        $this->authorize('update', $account);

        if ($account->isSyncStopped()) {
            abort(403, 'Synchronization for this account is stopped by system. ['.$account->sync_state_comment.']');
        }

        $account->enableSync();

        return $this->response(
            new EmailAccountResource($account)
        );
    }

    /**
     * Disable synchronization for the given email account.
     */
    public function disable(string $id): JsonResponse
    {
        $account = EmailAccount::withCommon()->findOrFail($id);

        $this->authorize('update', $account);

        $account->setSyncState(
            SyncState::DISABLED,
            'Account synchronization disabled by user.'
        );

        return $this->response(
            new EmailAccountResource($account)
        );
    }
}
