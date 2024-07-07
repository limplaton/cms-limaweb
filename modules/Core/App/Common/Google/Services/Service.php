<?php
 

namespace Modules\Core\App\Common\Google\Services;

use Google\Client;
use Google\Service as GoogleService;

class Service
{
    protected Client $client;

    protected GoogleService $service;

    /**
     * Initialize new Service instance.
     */
    public function __construct(Client $client, string|GoogleService $service, ...$params)
    {
        $this->client = $client;

        $this->service = ! $service instanceof GoogleService ?
            new $service($this->client, ...$params) :
            $service;
    }

    /**
     * Dynamically access the service
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->service->{$key};
    }
}
