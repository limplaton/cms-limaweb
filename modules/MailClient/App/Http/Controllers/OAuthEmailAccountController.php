<?php
 

namespace Modules\MailClient\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\App\Common\OAuth\OAuthManager;
use Modules\Core\App\Facades\OAuthState;
use Modules\MailClient\App\Enums\EmailAccountType;

class OAuthEmailAccountController extends Controller
{
    /**
     * OAuth connect email account
     */
    public function connect(string $type, string $providerName, Request $request, OAuthManager $manager): RedirectResponse
    {
        abort_if(
            ! $request->user()->isSuperAdmin() && EmailAccountType::from($type) === EmailAccountType::SHARED,
            403,
            'Unauthorized action.'
        );

        return redirect($manager->createProvider($providerName)
            ->getAuthorizationUrl(['state' => $this->createState($request, $type, $manager)]));
    }

    /**
     * Create state.
     */
    protected function createState(Request $request, string $type, OAuthManager $manager): string
    {
        return OAuthState::putWithParameters([
            'return_url' => '/mail/accounts?viaOAuth=true',
            'period' => $request->period,
            'email_account_type' => $type,
            're_auth' => $request->re_auth,
            'key' => $manager->generateRandomState(),
        ]);
    }
}
