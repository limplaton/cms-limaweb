<?php
 

namespace Modules\Activities\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\App\Common\OAuth\OAuthManager;
use Modules\Core\App\Facades\OAuthState;

class OAuthCalendarController extends Controller
{
    /**
     * OAuth connect email account.
     */
    public function connect(string $providerName, Request $request, OAuthManager $manager): RedirectResponse
    {
        return redirect($manager->createProvider($providerName)
            ->getAuthorizationUrl(['state' => $this->createState($request, $manager)]));
    }

    /**
     * Create state.
     */
    protected function createState(Request $request, OAuthManager $manager): string
    {
        return OAuthState::putWithParameters([
            'return_url' => '/calendar/sync?viaOAuth=true',
            're_auth' => $request->re_auth,
            'key' => $manager->generateRandomState(),
        ]);
    }
}
