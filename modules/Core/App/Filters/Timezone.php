<?php
 

namespace Modules\Core\App\Filters;

use Modules\Core\App\Facades\Timezone as Facade;

class Timezone extends Optionable
{
    /**
     * Resolve the filter options.
     */
    public function resolveOptions(): array
    {
        return collect(Facade::toArray())->map(function ($timezone) {
            return [$this->labelKey => $timezone, $this->valueKey => $timezone];
        })->all();
    }

    /**
     * Defines a filter type
     */
    public function type(): string
    {
        return 'select';
    }
}
