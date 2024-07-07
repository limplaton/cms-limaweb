<?php
 

namespace Modules\Core\App\Common\Microsoft\Services\Batch;

class BatchPatchRequest extends BatchRequest
{
    /**
     * Initialize new BatchPatchRequest instance.
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
        return 'PATCH';
    }
}
