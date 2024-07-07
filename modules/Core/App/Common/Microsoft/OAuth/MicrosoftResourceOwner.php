<?php
 

namespace Modules\Core\App\Common\Microsoft\OAuth;

use Modules\Core\App\Common\OAuth\ResourceOwner;

class MicrosoftResourceOwner extends ResourceOwner
{
    /**
     * Get the resource owner email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->response['email'] ?? $this->response['userPrincipalName'];
    }
}
