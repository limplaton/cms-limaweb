<?php
 

namespace Modules\MailClient\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\MailClient\App\Models\EmailAccount;

class EmailAccountPrimaryStateController extends ApiController
{
    /**
     * Mark the given account as primary for the current user.
     */
    public function update(string $id): JsonResponse
    {
        /** @var \Modules\MailClient\App\Models\EmailAccount */
        $account = EmailAccount::findOrFail($id);

        $this->authorize('view', $account);

        /** @var \Modules\Users\App\Model\User&\Modules\Core\App\Contracts\Metable */
        $user = auth()->user();

        $account->markAsPrimary($user);

        return $this->response('', JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Remove primary account for the current user.
     */
    public function destroy(): JsonResponse
    {
        /** @var \Modules\Users\App\Model\User&\Modules\Core\App\Contracts\Metable */
        $user = auth()->user();

        EmailAccount::unmarkAsPrimary($user);

        return $this->response('', JsonResponse::HTTP_NO_CONTENT);
    }
}
