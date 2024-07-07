<?php
 

namespace Modules\Core\App\Filters;

class Select extends Optionable
{
    /**
     * Defines a filter type
     */
    public function type(): string
    {
        return 'select';
    }
}
