<?php
 

namespace Modules\Core\App\Common\OAuth;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class ResourceOwner implements ResourceOwnerInterface
{
    public function __construct(protected array $response)
    {
    }

    /**
     * Get the owner identifier
     *
     * @return string
     */
    public function getId()
    {
        return $this->response['id'];
    }

    /**
     * Get the resource owner email
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->response['email'] ?? null;
    }

    /**
     * Returns the raw resource owner response.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}
