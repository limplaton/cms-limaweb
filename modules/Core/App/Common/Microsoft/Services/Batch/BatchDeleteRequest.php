<?php
 

namespace Modules\Core\App\Common\Microsoft\Services\Batch;

class BatchDeleteRequest extends BatchRequest
{
    /**
     * Get request method
     *
     * @return string
     */
    public function getMethod()
    {
        return 'DELETE';
    }
}
