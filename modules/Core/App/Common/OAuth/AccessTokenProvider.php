<?php
 

namespace Modules\Core\App\Common\OAuth;

class AccessTokenProvider
{
    /**
     * Initialize the acess token provider class
     */
    public function __construct(protected string $token, protected string $email)
    {
    }

    /**
     * Get the access token
     */
    public function getAccessToken(): string
    {
        return $this->token;
    }

    /**
     * Get the token email adress
     */
    public function getEmail(): string
    {
        return $this->email;
    }
}
