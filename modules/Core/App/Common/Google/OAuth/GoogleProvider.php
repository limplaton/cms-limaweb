<?php
 

namespace Modules\Core\App\Common\Google\OAuth;

use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;

class GoogleProvider extends Google
{
    /**
     * Generate a user object from a successful user details request.
     */
    protected function createResourceOwner(array $response, AccessToken $token): GoogleUser
    {
        return new GoogleResourceOwner($response);
    }
}
