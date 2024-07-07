<?php
 

namespace Modules\Core\App\Rules;

use Illuminate\Validation\Rules\Unique;
use Modules\Core\App\Support\Makeable;

class UniqueRule extends Unique
{
    use Makeable;

    /**
     * Create a new rule instance.
     */
    public function __construct(string $model, mixed $ignore = null, ?string $column = 'NULL')
    {
        parent::__construct(
            app($model)->getTable(),
            $column
        );

        if (! is_null($ignore)) {
            $ignoredId = is_int($ignore) ? $ignore : (request()->route($ignore) ?: null);

            $this->ignore($ignoredId);
        }
    }
}
