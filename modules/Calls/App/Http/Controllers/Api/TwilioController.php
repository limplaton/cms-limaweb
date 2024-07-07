<?php
 

namespace Modules\Calls\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\App\Http\Controllers\ApiController;
use Twilio\Rest\Client;

class TwilioController extends ApiController
{
    /**
     * Retrieve available incoming phone numbers.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->response(
            collect((new Client(
                $request->input('account_sid'),
                $request->input('auth_token')
            ))->incomingPhoneNumbers->read([], 50))
                ->map(function ($number) {
                    return $number->toArray();
                })->all()
        );
    }

    /**
     * Disconnect the Twilio Integration
     *
     * NOTE: We won't remove the created application SID because if the user
     * want to connect the integration again, to use the same app
     */
    public function destroy(): JsonResponse
    {
        settings()->forget('twilio_auth_token');
        settings()->forget('twilio_account_sid');
        // settings()->forget('twilio_app_sid');
        settings()->forget('twilio_number')->save();

        return $this->response('', JsonResponse::HTTP_NO_CONTENT);
    }
}
