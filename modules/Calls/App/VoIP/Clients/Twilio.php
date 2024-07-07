<?php
 

namespace Modules\Calls\App\VoIP\Clients;

use Illuminate\Http\Request;
use Modules\Calls\App\VoIP\Call;
use Modules\Calls\App\VoIP\Contracts\ReceivesEvents;
use Modules\Calls\App\VoIP\Contracts\Tokenable;
use Modules\Calls\App\VoIP\Contracts\VoIPClient;
use Modules\Calls\App\VoIP\VoIP;
use Modules\Users\App\Models\User;
use Twilio\Jwt\ClientToken;
use Twilio\Security\RequestValidator;
use Twilio\TwiML\VoiceResponse;

class Twilio implements ReceivesEvents, Tokenable, VoIPClient
{
    /**
     * Holds the events URL
     *
     * @var string
     */
    protected $eventsUrl;

    /**
     * @var \Twilio\Jwt\ClientToken
     */
    protected $clientToken;

    /**
     * Initialize new Twilio instance.
     */
    public function __construct(protected array $config)
    {
        $this->clientToken = new ClientToken($config['accountSid'], $config['authToken']);
    }

    /**
     * Handle the VoIP service events request
     */
    public function events(Request $request): VoiceResponse
    {
        return $this->createResponse();
    }

    /**
     * Get the Call class from the given webhook request
     */
    public function getCall(Request $request): Call
    {
        return new Call(
            $this->number(),
            $request->input('From'),
            $request->input('To'),
            $request->input('DialCallStatus')
        );
    }

    /**
     * Create new outgoing call from request
     *
     * @param  string  $phoneNumber
     */
    public function newOutgoingCall($phoneNumber, Request $request): VoiceResponse
    {
        $response = $this->createResponse();

        $response->dial()
            ->setCallerId($this->number())
            ->setAction($this->eventsUrl)
            ->number($phoneNumber);

        return $response;
    }

    /**
     * Create new incoming call from request
     */
    public function newIncomingCall(Request $request): VoiceResponse
    {
        $response = $this->createResponse();
        $dial = $response->dial();

        $this->getLastActiveUsers()->each(function ($user) use ($dial) {
            $dial->client($user->getKey());
        });

        $dial->setAction($this->eventsUrl);

        return $response;
    }

    /**
     * Set the events Url
     */
    public function setEventsUrl(string $url): static
    {
        $this->eventsUrl = $url;

        return $this;
    }

    /**
     * Create new client token for the logged in user
     */
    public function newToken(Request $request): string
    {
        $this->clientToken->allowClientOutgoing($this->config['applicationSid']);

        // Set allowed incoming client, @see method newIncomingCall
        $this->clientToken->allowClientIncoming($request->user()->getKey());

        return $this->clientToken->generateToken($request->input('ttl', 3600));
    }

    /**
     * Validate the request for authenticity
     *
     * @return void
     *
     * @see  https://www.twilio.com/docs/usage/security#http-authentication
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function validateRequest(Request $request)
    {
        if (! $signature = $request->server('HTTP_X_TWILIO_SIGNATURE')) {
            abort(403, 'The action you have requested is not allowed [Invalid signature].');
        }

        if ($ngrokUrl = VoIP::getNgrokUrl()) {
            $url = $ngrokUrl.$request->server('REQUEST_URI');
        } elseif ($exposeUrl = VoIP::getExposeUrl()) {
            $url = $exposeUrl.$request->server('REQUEST_URI');
        } else {
            $url = $request->fullUrl();
        }

        $validator = new RequestValidator($this->config['authToken']);

        if (! $validator->validate($signature, $url, $_POST)) {
            abort(404);
        }
    }

    /**
     * Get the Twilio phone number
     *
     * @return string
     */
    public function number()
    {
        return $this->config['number'];
    }

    /**
     *  Get the users that were last active in the last 4 hours
     *  to be available as allowed users to receive calls for the client sdk
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getLastActiveUsers()
    {
        return User::where('last_active_at', '>=', now()->subHours(4))->get();
    }

    /**
     * Create new voice response
     */
    protected function createResponse(): VoiceResponse
    {
        return new VoiceResponse;
    }
}
