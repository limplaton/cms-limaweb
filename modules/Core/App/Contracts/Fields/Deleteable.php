<?php
 

namespace Modules\Core\App\Contracts\Fields;

use Modules\Core\App\Models\Model;

interface Deleteable
{
    /**
     * Handle the field deletition.
     */
    public function delete(Model $model): void;
}
