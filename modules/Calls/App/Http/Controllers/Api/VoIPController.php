<?php
 

namespace Modules\Calls\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Calls\App\VoIP\Events\IncomingCallMissed;
use Modules\Calls\App\VoIP\VoIP;
use Modules\Core\App\Http\Controllers\ApiController;

class VoIPController extends ApiController
{
    /**
     * Call events
     *
     * @return mixed
     */
    public function events(Request $request)
    {
        VoIP::validateRequest($request);

        $call = VoIP::getCall($request);

        if ($call->isMissedIncoming()) {
            event(new IncomingCallMissed($call));
        }

        return VoIP::events($request);
    }

    /**
     * Initiate new call
     *
     * @return mixed
     */
    public function newCall(Request $request)
    {
        VoIP::validateRequest($request);

        if (VoIP::shouldReceivesEvents()) {
            VoIP::setEventsUrl(VoIP::eventsUrl());
        }

        if ($request->boolean('viaApp')) {
            return VoIP::newOutgoingCall(
                $request->input('To'),
                $request
            );
        }

        return VoIP::newIncomingCall($request);
    }

    /**
     * Create a new client token.
     */
    public function newToken(Request $request): JsonResponse
    {
        return $this->response(['token' => VoIP::newToken($request)]);
    }
}
