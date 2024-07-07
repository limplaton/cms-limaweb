<?php
 

namespace Modules\MailClient\App\Synchronization;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Modules\Core\App\Common\Synchronization\SyncState;
use Modules\MailClient\App\Client\Exceptions\ConnectionErrorException;
use Modules\MailClient\App\Client\Exceptions\RateLimitExceededException;
use Modules\MailClient\App\Client\Exceptions\ServiceUnavailableException;

trait HandlesSynchronizationErrors
{
    protected function handleConnectionErrorException(ConnectionErrorException $e): void
    {
        // Before disabling the account, we will check if this is the fifth time the connection fails,
        // it may be a temporary connection error caused by SMTP rate limiting or temporary network failure
        // https://laracasts.com/discuss/channels/laravel/errorexception-fgets-ssl-connection-reset-by-peer?page=1&replyId=843475
        $fails = (int) $this->account->getMeta('connection-fails') + 1;

        if ($fails < 5) {
            $this->account->setMeta('connection-fails', $fails);
        } else {
            $this->account->setAuthRequired();

            $this->account->setSyncState(
                SyncState::DISABLED,
                'Email account synchronization disabled because of failed authentication, re-authenticate and enable sync for this account.'
            );

            Log::debug(
                "Mail account ({$this->account->email}) connection error: {$e->getMessage()}"
            );

            $this->error(
                'Email account synchronization disabled because of failed authentication ['.$e->getMessage().'].'
            );

            // To broadcast
            $this->synced = true;
        }
    }

    protected function handleDecryptException(): void
    {
        $this->account->setSyncState(
            SyncState::DISABLED,
            'Failed to decrypt account password, re-add password and enable sync for this account.'
        );
    }

    protected function handleEmptyRefreshTokenException(): void
    {
        $this->account->setSyncState(
            SyncState::STOPPED,
            'The sync for this email account is disabled because of empty refresh token, try to remove the app from your '.explode('@', $this->account->email)[1].' account connected apps section and re-connect the account again from the Connected Accounts page.'
        );

        $this->error('Email account synchronization stopped because empty refresh token.');
    }

    protected function handleIdentityProviderException(IdentityProviderException $e): void
    {
        // Handle account grant error and account deletition after they are connected
        // e.q. G Suite account ads a user with email, the email connected to the CRM
        // but after that the email is deleted, in this case, we need to catch this error and disable
        // the account sync to stop any exceptions thrown each time the synchronizer runs
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

        $this->account->setSyncState(
            SyncState::STOPPED,
            'Email account sync stopped because of an OAuth error, try reconnecting the account. '.$message
        );

        $this->error('Email account synchronization stopped because of identity provider exception.');
    }

    protected function handleRateLimitExceededOrServiceUnavilableException(RateLimitExceededException|ServiceUnavailableException $e)
    {
        if ($retryAfter = $e->retryAfter()) {
            $this->account->holdSyncUntil($retryAfter);
        }

        $this->error(sprintf(
            'Skipping sync for account %s, rescheduled for %s, account rate limit exceeded or service unavailable.',
            $this->account->email,
            $retryAfter ?: 'N/A'
        ));
    }
}
