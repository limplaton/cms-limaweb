<?php
 

namespace Modules\Core\App\Filters;

use Illuminate\Support\Collection;
use Modules\Core\App\Http\Requests\ResourceRequest;

trait ResolvesFilters
{
    /**
     *  Get the available filters for the user
     *
     * @return \Illuminate\Support\Collection<object, \Modules\Core\App\Filters\Filter>
     */
    public function resolveFilters(ResourceRequest $request): Collection
    {
        $filters = $this->filters($request);

        $collection = is_array($filters) ? new Collection($filters) : $filters;

        return $collection->filter->authorizedToSee()->values();
    }

    /**
     * @codeCoverageIgnore
     *
     * Get the defined filters
     */
    public function filters(ResourceRequest $request): array|Collection
    {
        return [];
    }
}
