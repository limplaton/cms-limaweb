<?php
 

namespace Modules\Core\App\Filters;

class Numeric extends Filter
{
    /**
     * Defines a filter type
     */
    public function type(): string
    {
        return 'numeric';
    }
}
