<?php
 

namespace Modules\Core\App\Resource;

use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Models\Model;

trait HasResourceEvents
{
    /**
     * Handle the "beforeCreate" resource record hook.
     */
    public function beforeCreate(Model $model, ResourceRequest $request): void
    {
    }

    /**
     * Handle the "afterCreate" resource record hook.
     */
    public function afterCreate(Model $model, ResourceRequest $request): void
    {
    }

    /**
     * Handle the "beforeUpdate" resource record hook.
     */
    public function beforeUpdate(Model $model, ResourceRequest $request): void
    {
    }

    /**
     * Handle the "afterUpdate" resource record hook.
     */
    public function afterUpdate(Model $model, ResourceRequest $request): void
    {
    }

    /**
     * Handle the "beforeDelete" resource record hook.
     */
    public function beforeDelete(Model $model, ResourceRequest $request): void
    {
    }

    /**
     * Handle the "afterDelete" resource record hook.
     */
    public function afterDelete(Model $model, ResourceRequest $request): void
    {
    }
}
