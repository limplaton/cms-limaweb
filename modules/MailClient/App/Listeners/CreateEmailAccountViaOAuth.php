<?php
 

namespace Modules\MailClient\App\Listeners;

use Illuminate\Http\Request;
use Modules\Core\App\Facades\OAuthState;
use Modules\Core\App\Models\OAuthAccount;
use Modules\MailClient\App\Client\ClientManager;
use Modules\MailClient\App\Client\ConnectionType;
use Modules\MailClient\App\Client\Exceptions\UnauthorizedException;
use Modules\MailClient\App\Enums\EmailAccountType;
use Modules\MailClient\App\Models\EmailAccount;
use Modules\MailClient\App\Services\EmailAccountService;

class CreateEmailAccountViaOAuth
{
    /**
     * Initialize new CreateEmailAccountViaOAuth instance.
     */
    public function __construct(protected EmailAccountService $service, protected Request $request)
    {
    }

    /**
     * Handle Microsoft email account connection finished.
     */
    public function handle(object $event): void
    {
        $oAuthAccount = $event->account;
        $account = EmailAccount::where('email', $oAuthAccount->email)->first();

        // Connection not intended for email account
        // Connection can be invoke via /oauth/accounts route or calendar because of re-authentication
        $emailAccountBeingConnected = ! is_null(OAuthState::getParameter('email_account_type'));

        if (! $emailAccountBeingConnected) {
            // We will check if this OAuth account actually exists and if yes,
            // we will make sure that the account is usable and it does not require authentication in database
            // as well that sync is enabled in case stopped previously e.q. because of refresh token
            // in this case, the user won't need to re-authenticate via the email accounts index area again
            if ($account) {
                $this->makeSureAccountIsUsable($account, $oAuthAccount);
            }

            return;
        }

        if (! $account) {
            if (! $account = $this->createEmailAccount($oAuthAccount)) {
                return;
            }
        } elseif ((string) OAuthState::getParameter('re_auth') !== '1') {
            set_alert(__('mailclient::mail.account.already_connected'), 'warning');
        }

        $this->makeSureAccountIsUsable($account, $oAuthAccount);
    }

    /**
     * Make sure that the account is usable
     *
     * Sets requires autentication to false as well enabled sync again if is stopped by system
     */
    protected function makeSureAccountIsUsable(EmailAccount $account, OAuthAccount $oAuthAccount): void
    {
        // Update the access_token_id because it's not set in the createEmailAccount method
        // This useful when users deletes an OAuth account and this account
        // was connected to an email account, in this case, becauase we found
        // a disabled email account (we disable email account before oAuth deletition) with the same e-mail address,
        // we will just re-connect the OAuth account with the email account when user is re-connecting again.
        $account->access_token_id = $oAuthAccount->id;

        // This method call updates the "access_token_id" as well as it's saving the model.
        $account->setAuthRequired(false);

        // If the sync is stopped, probably it's because of empty refresh token or
        // failed authenticated for some reason, when reconnected, enable sync again
        if ($account->isSyncStopped()) {
            $account->enableSync();
        }
    }

    /**
     * Create the email account
     *
     * @param  \Modules\Core\App\Models\OAuthAccount  $oAuthAccount
     * @return \Modules\MailClient\App\Models\EmailAccount
     */
    protected function createEmailAccount($oAuthAccount)
    {
        $payload = [
            'connection_type' => $oAuthAccount->type == 'microsoft' ?
                ConnectionType::Outlook :
                ConnectionType::Gmail,
            'email' => $oAuthAccount->email,
        ];

        // When initially connected an account e.q. Gmail, we will try to retrieve the folders
        // however, if the user did not enabled the Gmail API, will throw an error, catching the exception
        // below will make sure that the user will actually see an error message so he can take steps

        try {
            $folders = ClientManager::createClient(
                $payload['connection_type'],
                $oAuthAccount->tokenProvider()
            )
                ->getImap()
                ->getFolders();

            $payload['folders'] = $folders->toArray();
        } catch (UnauthorizedException $e) {
            set_alert($e->getMessage(), 'warning');

            return;
        }

        $payload['initial_sync_from'] = OAuthState::getParameter('period');

        if ($this->isPersonal()) {
            $payload['user_id'] = $this->request->user()->id;
        }

        return $this->service->create($payload);
    }

    /**
     * Check whether the account is personal
     */
    protected function isPersonal(): bool
    {
        return EmailAccountType::tryFrom(
            OAuthState::getParameter('email_account_type')
        ) === EmailAccountType::PERSONAL;
    }
}
