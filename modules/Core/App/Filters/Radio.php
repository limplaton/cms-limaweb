<?php
 

namespace Modules\Core\App\Filters;

class Radio extends Optionable
{
    /**
     * Defines a filter type
     */
    public function type(): string
    {
        return 'radio';
    }
}
