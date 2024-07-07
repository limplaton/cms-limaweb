<?php
 

namespace Modules\Core\App\Filters;

class MultiSelect extends Select
{
    /**
     * Defines a filter type
     */
    public function type(): string
    {
        return 'multi-select';
    }
}
