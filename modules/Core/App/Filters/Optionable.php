<?php
 

namespace Modules\Core\App\Filters;

use Modules\Core\App\Support\HasOptions;

class Optionable extends Filter
{
    use HasOptions;

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'valueKey' => $this->valueKey,
            'labelKey' => $this->labelKey,
            'options' => $this->resolveOptions(),
        ]);
    }
}
