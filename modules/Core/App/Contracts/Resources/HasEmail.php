<?php
 

namespace Modules\Core\App\Contracts\Resources;

interface HasEmail
{
    /**
     * Get the resource model email address field name.
     */
    public function emailAddressField(): string;
}
