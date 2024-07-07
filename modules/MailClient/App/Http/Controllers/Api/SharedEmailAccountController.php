<?php
 

namespace Modules\MailClient\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\MailClient\App\Http\Resources\EmailAccountResource;
use Modules\MailClient\App\Models\EmailAccount;

class SharedEmailAccountController extends ApiController
{
    /**
     * Display shared email accounts.
     */
    public function __invoke(): JsonResponse
    {
        $accounts = EmailAccount::withCommon()
            ->shared()
            ->orderBy('email')
            ->get();

        return $this->response(
            EmailAccountResource::collection($accounts)
        );
    }
}
