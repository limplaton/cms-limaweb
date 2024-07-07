<?php
 

namespace Modules\Core\App\Contracts\Resources;

use Modules\Core\App\Models\Model;

interface Cloneable
{
    /**
     * Clone the resource record from the given id
     */
    public function clone(Model $model, int $userId): Model;
}
