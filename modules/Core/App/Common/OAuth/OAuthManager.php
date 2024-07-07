<?php
 

namespace Modules\Core\App\Common\OAuth;

use League\OAuth2\Client\Grant\RefreshToken;
use Modules\Core\App\Common\Google\OAuth\GoogleProvider;
use Modules\Core\App\Common\Microsoft\OAuth\MicrosoftProvider;
use Modules\Core\App\Common\OAuth\Events\OAuthAccountConnected;
use Modules\Core\App\Models\OAuthAccount;

class OAuthManager
{
    /**
     * @var null|int
     */
    protected $userId;

    /**
     * Set the application user the token is related to
     *
     * @param  int  $userId
     * @return static
     */
    public function forUser($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Generates random state
     *
     * @param  int  $length
     * @return string
     */
    public function generateRandomState($length = 32)
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Connect OAuth account
     *
     * @param  string  $type
     * @param  string  $code
     * @return \Modules\Core\App\Models\OAuthAccount
     */
    public function connect($type, $code)
    {
        $provider = $this->createProvider($type);

        $accessToken = $provider->getAccessToken('authorization_code', [
            'code' => $code,
        ]);

        $user = $provider->getResourceOwner($accessToken);
        $account = $this->storeAccount($type, $accessToken, $user);

        event(new OAuthAccountConnected($account));

        return $account;
    }

    /**
     * Get the access token by email
     *
     * @param  string  $type
     * @param  string  $email
     * @return string
     */
    public function retrieveAccessToken($type, $email)
    {
        $account = $this->getAccount($type, $email);

        // Check if token is expired
        // Get current time + 5 minutes (to allow for time differences)
        if ($account->expires <= time() + 300) {
            // Token is expired (or very close to it) so let's refresh
            $newToken = $this->refreshToken($type, $account->refresh_token);

            return tap($newToken->getToken(), function ($refreshedToken) use ($newToken, $account) {
                $account->fill([
                    'access_token' => $refreshedToken,
                    'expires' => $newToken->getExpires(),
                ])->save();
            });
        }

        // Token is still valid, just return it
        return $account->access_token;
    }

    /**
     * Create OAuth Provider
     *
     * @param  string  $type
     * @return \Modules\Core\App\Contracts\OAuth\Provider
     */
    public function createProvider($type)
    {
        return $this->{'create'.ucfirst($type).'Provider'}();
    }

    /**
     * Create Google OAuth Provider
     *
     * @return \Modules\Core\App\Common\Google\OAuthProvider
     */
    public function createGoogleProvider()
    {
        $redirectUrl = config('integrations.google.redirect_url');

        return new GoogleProvider([
            'clientId' => config('integrations.google.client_id'),
            'clientSecret' => config('integrations.google.client_secret'),
            'redirectUri' => $redirectUrl ?: url(config('integrations.google.redirect_uri')),
            'accessType' => config('integrations.google.access_type'),
            'scopes' => config('integrations.google.scopes'),
        ]);
    }

    /**
     * Create Microsoft OAuth Provider
     *
     * @return \Modules\Core\App\Common\Google\OAuthProvider
     */
    public function createMicrosoftProvider()
    {
        $redirectUrl = config('integrations.microsoft.redirect_url');

        return new MicrosoftProvider([
            'clientId' => config('integrations.microsoft.client_id'),
            'clientSecret' => config('integrations.microsoft.client_secret'),
            'redirectUri' => $redirectUrl ?: url(config('integrations.microsoft.redirect_uri')),
            'scopes' => config('integrations.microsoft.scopes'),
        ]);
    }

    /**
     * Refresh the token based on a given refresh token
     *
     * @param  string  $type
     * @param  string  $refreshToken
     * @return \League\OAuth2\Client\Token\AccessTokenInterface
     */
    public function refreshToken($type, $refreshToken)
    {
        if (empty($refreshToken)) {
            throw new EmptyRefreshTokenException;
        }

        return $this->createProvider($type)->getAccessToken(new RefreshToken, [
            'refresh_token' => $refreshToken,
        ]);
    }

    /**
     * Get the access token account
     *
     * @param  string  $type
     * @param  string  $email
     * @return \Modules\Core\App\Models\OAuthAccount
     */
    public function getAccount($type, $email)
    {
        return OAuthAccount::where('type', $type)->where('email', $email)->first();
    }

    /**
     * Store account and it's tokens in the database
     *
     * @param  string  $type
     * @param  \League\OAuth2\Client\Token\AccessTokenInterface  $accessToken
     * @param  \Modules\Core\App\Common\OAuth\User  $user
     * @return \Modules\Core\App\Models\OAuthAccount
     */
    protected function storeAccount($type, $accessToken, $user)
    {
        $data = [
            'email' => $user->getEmail(),
            'access_token' => $accessToken->getToken(),
            'expires' => $accessToken->getExpires(),
            'oauth_user_id' => $user->getId(),
            'requires_auth' => false,
        ];

        // E.q. for Google, only it's returned on the first connection
        if ($refreshToken = $accessToken->getRefreshToken()) {
            $data['refresh_token'] = $refreshToken;
        }

        if ($this->userId) {
            $data['user_id'] = $this->userId;
        }

        return OAuthAccount::updateOrCreate(['email' => $data['email'], 'type' => $type], $data);
    }
}
