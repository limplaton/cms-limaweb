<?php
 

namespace Modules\Core\App\Actions;

use Illuminate\Support\Collection;
use Modules\Core\App\Http\Requests\ResourceRequest;

trait ResolvesActions
{
    /**
     * Get the available actions for the resource
     *
     * @return \Illuminate\Support\Collection<object, \Modules\Core\App\Actions\Action>
     */
    public function resolveActions(ResourceRequest $request): Collection
    {
        $actions = $this->actions($request);

        $collection = is_array($actions) ? new Collection($actions) : $actions;

        return $collection->filter->authorizedToSee()->values();
    }

    /**
     * @codeCoverageIgnore
     *
     * Get the defined resource actions
     */
    public function actions(ResourceRequest $request): array|Collection
    {
        return [];
    }
}
