<?php
 

namespace Modules\MailClient\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\MailClient\App\Http\Resources\EmailAccountResource;
use Modules\MailClient\App\Models\EmailAccount;

class PersonalEmailAccountController extends ApiController
{
    /**
     * Display personal email accounts for the logged in user.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $accounts = EmailAccount::withCommon()
            ->personal((int) $request->user()->id)
            ->orderBy('email')
            ->get();

        return $this->response(
            EmailAccountResource::collection($accounts)
        );
    }
}
