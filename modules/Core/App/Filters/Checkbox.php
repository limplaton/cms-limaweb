<?php
 

namespace Modules\Core\App\Filters;

class Checkbox extends Optionable
{
    /**
     * Defines a filter type
     */
    public function type(): string
    {
        return 'checkbox';
    }
}
