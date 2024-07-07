<?php
 

namespace Modules\MailClient\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\MailClient\App\Criteria\EmailAccountsForUserCriteria;
use Modules\MailClient\App\Models\ScheduledEmail;

class ScheduledEmailSendController extends ApiController
{
    /**
     * Send the given scheduled email.
     */
    public function __invoke(string $id): JsonResponse
    {
        $message = ScheduledEmail::withWhereHas(
            'account', fn ($query) => $query->criteria(EmailAccountsForUserCriteria::class)
        )->findOrFail($id);

        if ($message->isSending()) {
            abort(409, 'This email is already being sent in background.');
        } elseif ($message->isSent()) {
            abort(409, 'This email is already sent.');
        }

        $message->send();

        return $this->response('', JsonResponse::HTTP_NO_CONTENT);
    }
}
