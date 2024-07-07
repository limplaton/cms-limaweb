<?php
 

namespace Modules\Core\App\Common\Microsoft\Services\Batch;

class BatchGetRequest extends BatchRequest
{
    /**
     * Get request method
     *
     * @return string
     */
    public function getMethod()
    {
        return 'GET';
    }
}
