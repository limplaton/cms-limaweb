<?php
 

namespace Modules\Core\App\Common\Microsoft;

use Illuminate\Contracts\Support\Arrayable;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Http\GraphCollectionRequest;
use Modules\Core\App\Common\Microsoft\Services\Batch\BatchRequests;
use Modules\Core\App\Common\Microsoft\Services\Batch\Request as BatchRequest;
use Modules\Core\App\Common\OAuth\AccessTokenProvider;
use Modules\Core\App\Common\OAuth\OAuthManager;

class Client
{
    /**
     * @var \Microsoft\Graph\Graph
     */
    protected $client;

    /**
     * The email address to use to fetch the token
     *
     * @var string
     */
    protected static $email;

    /**
     * Microsoft Graph Base URL
     */
    protected static string $baseUrl = 'https://graph.microsoft.com/';

    /**
     * Microsoft Graph API Version
     */
    protected static string $apiVersion = 'v1.0';

    /**
     * https://learn.microsoft.com/en-us/graph/outlook-immutable-id
     */
    protected bool $immutable = false;

    /**
     * Provide a connector for the access token
     */
    public function connectUsing(string|AccessTokenProvider $connector): static
    {
        static::$email = is_string($connector) ? $connector : $connector->getEmail();

        // Reset the client so the next time can be retrieved with the new connector
        $this->client = null;

        return $this;
    }

    /**
     * Set the Microsoft Graph API Version
     *
     *
     * @return static
     */
    public function setApiVersion(string $version)
    {
        static::$apiVersion = $version;

        return $this;
    }

    /**
     * Get the Microsoft Graph API Version
     */
    public function getApiVersion(): string
    {
        return static::$apiVersion;
    }

    /**
     * Set the Microsoft Graph API Base URL
     */
    public function setBaseUrl(string $url): static
    {
        static::$baseUrl = $url;

        return $this;
    }

    /**
     * Execute callback in immutable state
     *
     *
     * @return mixed
     */
    public function immutable(callable $callback)
    {
        $this->immutable = true;

        try {
            return call_user_func($callback);
        } finally {
            $this->immutable = false;
        }
    }

    /**
     * The function can be used to iterate over a collection request
     * to get all the results from the collection via all the pages
     */
    public function iterateCollectionRequest(GraphCollectionRequest $collection): array
    {
        $entities = [];

        while (! $collection->isEnd()) {
            $data = $collection->getPage();

            // https://github.com/microsoftgraph/msgraph-sdk-php/issues/46
            if (is_array($data)) {
                $entities = array_merge($entities, $data);
            }
        }

        return $entities;
    }

    /**
     * Create request
     *
     * @param  string  $requestType  Request type ('get', 'post', 'patch', 'put', 'delete')
     * @param  string  $endpoint  Graph endpoint
     * @return \Microsoft\Graph\Http\GraphRequest
     */
    public function createRequest($requestType, $endpoint)
    {
        return $this->addHeaders(
            $this->getClient()->createRequest($requestType, $endpoint)
        );
    }

    /**
     * Create POST request
     *
     * @param  string  $endpoint
     * @param  mixed  $body
     * @return \Microsoft\Graph\Http\GraphRequest
     */
    public function createPostRequest($endpoint, $body = null)
    {
        $request = $this->createRequest('POST', $endpoint);

        if ($body) {
            $request->attachBody($body instanceof Arrayable ? $body->toArray() : $body);
        }

        return $request;
    }

    /**
     * Create PATCH request
     *
     * @param  string  $endpoint
     * @param  mixed  $body
     * @return \Microsoft\Graph\Http\GraphRequest
     */
    public function createPatchRequest($endpoint, $body = null)
    {
        $request = $this->createRequest('PATCH', $endpoint);

        if ($body) {
            $request->attachBody($body instanceof Arrayable ? $body->toArray() : $body);
        }

        return $request;
    }

    /**
     * Create PUT request
     *
     * @param  string  $endpoint
     * @param  mixed  $body
     * @return \Microsoft\Graph\Http\GraphRequest
     */
    public function createPutRequest($endpoint, $body = null)
    {
        $request = $this->createRequest('PUT', $endpoint);

        if ($body) {
            $request->attachBody($body instanceof Arrayable ? $body->toArray() : $body);
        }

        return $request;
    }

    /**
     * Create GET request
     *
     * @param  string  $endpoint
     * @return \Microsoft\Graph\Http\GraphRequest
     */
    public function createGetRequest($endpoint)
    {
        return $this->createRequest('GET', $endpoint);
    }

    /**
     * Create DELETE request
     *
     * @param  string  $endpoint
     * @return \Microsoft\Graph\Http\GraphRequest
     */
    public function createDeleteRequest($endpoint)
    {
        return $this->createRequest('DELETE', $endpoint);
    }

    /**
     * Create collection request
     *
     * @param  string  $requestType  Request type ('get', 'post', 'patch', 'put', 'delete')
     * @param  string  $endpoint  Graph endpoint
     * @return \Microsoft\Graph\Http\GraphCollectionRequest
     */
    public function createCollectionRequest($requestType, $endpoint)
    {
        return $this->addHeaders(
            $this->getClient()->createCollectionRequest($requestType, $endpoint)
        );
    }

    /**
     * Create collection GET request
     *
     * @param  string  $endpoint
     * @return \Microsoft\Graph\Http\GraphCollectionRequest
     */
    public function createCollectionGetRequest($endpoint)
    {
        return $this->createCollectionRequest('GET', $endpoint);
    }

    /**
     * Create batch request
     *
     * @return \Modules\Core\App\Common\Microsoft\Services\Batch\Request
     */
    public function createBatchRequest(BatchRequests $requests)
    {
        $request = $this->createCollectionRequest('POST', '/$batch');

        return new BatchRequest($request, $requests);
    }

    /**
     * Add common headers for the request
     *
     * @param  \Microsoft\Graph\Http\GraphRequest  $request
     * @return \Microsoft\Graph\Http\GraphRequest
     */
    protected function addHeaders($request)
    {
        if ($preferredTimezone = config('integrations.microsoft.prefer_timezone')) {
            $request->addHeaders(['Prefer' => 'outlook.timezone="'.$preferredTimezone.'"']);
        }

        if ($this->immutable) {
            $request->addHeaders(['Prefer' => 'IdType="ImmutableId"']);
        }

        return $request;
    }

    /**
     * Get the client graph class
     */
    public function getClient(): Graph
    {
        // In case the version is dynamically changed
        // Update in the current Graph instance too
        if (! is_null($this->client)) {
            return $this->client->setApiVersion(self::$apiVersion);
        }

        return $this->client = (new Graph)
            ->setAccessToken((new OAuthManager)->retrieveAccessToken('microsoft', static::$email))
            ->setBaseUrl(self::$baseUrl)
            ->setApiVersion(self::$apiVersion);
    }
}
