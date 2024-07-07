<?php
 

namespace Modules\Core\App\Common\Microsoft\Services\Batch;

class BatchPostRequest extends BatchRequest
{
    /**
     * Initialize new BatchPostRequest instance.
     *
     * @param  string  $url
     * @param  array  $body
     */
    public function __construct($url, $body = [])
    {
        parent::__construct($url, $body);
        $this->asJson();
    }

    /**
     * Get request method
     *
     * @return string
     */
    public function getMethod()
    {
        return 'POST';
    }
}
