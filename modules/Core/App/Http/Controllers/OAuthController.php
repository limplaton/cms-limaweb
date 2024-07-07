<?php
 

namespace Modules\Core\App\Http\Controllers;

use App\Http\Controllers\Controller;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Modules\Core\App\Common\OAuth\OAuthManager;
use Modules\Core\App\Facades\OAuthState;

class OAuthController extends Controller
{
    /**
     * The route to redirect if there is an error.
     */
    protected string $onErrorRedirectTo = '/dashboard';

    /**
     * Initialize OAuth Controller.
     */
    public function __construct(protected OAuthManager $manager)
    {
    }

    /**
     * Connect OAuth Account.
     */
    public function connect(string $provider): RedirectResponse
    {
        $state = $this->manager->generateRandomState();

        OAuthState::put($state);

        return redirect($this->manager->createProvider($provider)->getAuthorizationUrl(['state' => $state]));
    }

    /**
     * Callback for OAuth Account.
     */
    public function callback(string $provider, Request $request): RedirectResponse
    {
        if ($request->error) {
            set_alert($request->error_description ?: $request->error, 'danger');

            // Got an error, probably user denied access
            return redirect($this->onErrorRedirectTo);
        } elseif (! OAuthState::validate($request->state)) {
            set_alert(__('core::oauth.invalid_state'), 'danger');

            return redirect($this->onErrorRedirectTo);
        }

        if ($request->has('code')) {
            try {
                $this->manager->forUser($request->user()->id)->connect($provider, $request->code);
            } catch (IdentityProviderException $e) {
                $message = $e->getMessage();
                $responseBody = $e->getResponseBody();

                if ($responseBody instanceof Response) {
                    $responseBody = $responseBody->getReasonPhrase();
                }

                if ($responseBody != $message) {
                    $message .= ' ['.is_array($responseBody) ?
                        ($responseBody['error_description'] ?? $responseBody['error'] ?? json_encode($responseBody)) :
                        $responseBody.']';
                }

                set_alert($message, 'danger');

                return redirect($this->onErrorRedirectTo);
            } catch (\Exception $e) {
                set_alert($e->getMessage(), 'danger');

                return redirect($this->onErrorRedirectTo);
            }

            $returnUrl = OAuthState::getParameter('return_url', '/oauth/accounts');

            // Check if the account previously required authentication (for re-authenticate)
            if ((string) OAuthState::getParameter('re_auth') === '1') {
                set_alert(__('core::oauth.re_authenticated'), 'success');
            }

            // Finally, forget the oauth state, the state is used in the listeners
            // to get parameters for the actual accounts data
            OAuthState::forget();

            return redirect($returnUrl);
        }
    }
}
