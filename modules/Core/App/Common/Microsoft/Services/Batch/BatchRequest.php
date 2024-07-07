<?php
 

namespace Modules\Core\App\Common\Microsoft\Services\Batch;

use Illuminate\Contracts\Support\Arrayable;
use Microsoft\Graph\Model\Entity;
use Modules\Core\App\Support\Makeable;

class BatchRequest implements Arrayable
{
    use Makeable;

    /**
     * @var string|int
     */
    protected $id;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var array|\Microsoft\Graph\Model\Entity
     */
    protected $body = [];

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var array
     */
    protected $dependsOn = [];

    /**
     * Initialize batch request
     *
     * @param  string  $url
     * @param  array|\Microsoft\Graph\Model\Entity  $body
     */
    public function __construct($url, $body = [])
    {
        $this->setUrl($url);
        $this->setBody($body);
    }

    /**
     * Set request id
     *
     * @param  string|int  $id
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get request id
     *
     * @return string|int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set request url
     *
     * @param  string  $url
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get request url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set request method
     *
     * @param  string  $method
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Get request method
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set request body
     *
     * @param  array|\Microsoft\Graph\Model\Entity  $body
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get request body
     *
     * @return array
     */
    public function getBody()
    {
        if ($this->body instanceof Entity) {
            $this->body->setOdataType('microsoft.graph.'.class_basename($this->body));

            return $this->body->jsonSerialize();
        }

        return $this->body;
    }

    /**
     * Set request headers
     *
     * @param  array  $headers
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Add request header
     *
     * @param  string  $name
     * @param  string  $value
     */
    public function addHeader($name, $value)
    {
        $this->headers = array_merge($this->headers, [$name => $value]);

        return $this;
    }

    /**
     * Set request header
     *
     * @param  string  $key
     * @param  string  $value
     */
    public function setHeader($key, $value)
    {
        $this->headers = array_merge($this->headers, [$key => $value]);

        return $this;
    }

    /**
     * Get the request headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Checks whether there is headers for the request
     *
     * @return array
     */
    public function hasHeaders()
    {
        return count($this->getHeaders()) > 0;
    }

    /**
     * Checks whether there is body for the request
     *
     * @return array
     */
    public function hasBody()
    {
        return count($this->getBody()) > 0;
    }

    /**
     * Get the value of dependsOn
     *
     * @return array
     */
    public function getDependsOn()
    {
        return $this->dependsOn;
    }

    /**
     * Set the value of dependsOn
     *
     *
     * @return static
     */
    public function setDependsOn(array $dependsOn)
    {
        $this->dependsOn = $dependsOn;

        return $this;
    }

    /**
     * Mark the request as JSON
     *
     * @return static
     */
    public function asJson()
    {
        return $this->setHeaders(['Content-Type' => 'application/json']);
    }

    /**
     * toArray
     *
     * @return array
     */
    public function toArray()
    {
        $payload = [
            'id' => $this->getId(),
            'method' => $this->getMethod(),
            'url' => $this->getUrl(),
        ];

        if ($this->hasBody()) {
            $payload['body'] = $this->getBody();
        }

        if ($this->hasHeaders()) {
            $payload['headers'] = $this->getHeaders();
        }

        if (count($this->getDependsOn()) > 0) {
            $payload['dependsOn'] = $this->getDependsOn();
        }

        return $payload;
    }
}
