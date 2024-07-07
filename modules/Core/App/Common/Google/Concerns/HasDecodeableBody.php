<?php
 

namespace Modules\Core\App\Common\Google\Concerns;

trait HasDecodeableBody
{
    /**
     * @return string
     */
    public function getDecodedBody($content)
    {
        return str_replace('_', '/', str_replace('-', '+', $content));
    }
}
