<?php
 

namespace Modules\Core\App\Filters;

class Text extends Filter
{
    /**
     * Defines a filter type
     */
    public function type(): string
    {
        return 'text';
    }
}
