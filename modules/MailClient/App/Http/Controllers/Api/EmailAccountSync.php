<?php
 

namespace Modules\MailClient\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\MailClient\App\Http\Resources\EmailAccountResource;
use Modules\MailClient\App\Models\EmailAccount;
use Modules\MailClient\App\Synchronization\Exceptions\SynchronizationInProgressException;

class EmailAccountSync extends ApiController
{
    /**
     * Invoke synchronization for the given email account.
     *
     * @throws \Modules\MailClient\App\Synchronization\Exceptions\SynchronizationInProgressException
     */
    public function __invoke(string $accountId): JsonResponse
    {
        $this->authorize('view', EmailAccount::findOrFail($accountId));

        $exitCode = Innoclapps::runCommand('mailclient:sync', [
            '--account' => $accountId,
            '--broadcast' => false,
            '--isolated' => 5,
        ]);

        if ($exitCode === 5) {
            throw new SynchronizationInProgressException;
        }

        return $this->response(
            new EmailAccountResource(
                EmailAccount::withCommon()->find($accountId)
            )
        );
    }
}
