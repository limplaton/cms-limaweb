<?php
 

namespace Modules\Core\App\Common\Google\Concerns;

trait HasHeaders
{
    /**
     * @var \Modules\Core\App\Common\Mail\Headers\HeadersCollection
     */
    protected $headers;

    /**
     * Get all headers for the configured part
     *
     * @return \Modules\Core\App\Common\Mail\Headers\HeadersCollection
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get single header value
     *
     * @return \Modules\Core\App\Common\Mail\Headers\Header|null
     */
    public function getHeader($name)
    {
        return $this->headers->find($name);
    }

    /**
     * Get single header value
     *
     * @return string|null
     */
    public function getHeaderValue($name)
    {
        $header = $this->getHeader($name);

        return $header ? $header->getValue() : null;
    }
}
