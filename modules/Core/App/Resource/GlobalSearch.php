<?php
 

namespace Modules\Core\App\Resource;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use JsonSerializable;
use Modules\Core\App\Contracts\Presentable;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Models\Model;

class GlobalSearch implements JsonSerializable
{
    /**
     * Initialize global search for the given resources.
     *
     * @param  \Modules\Core\App\Resource\Resource[]  $resources
     */
    public function __construct(protected ResourceRequest $request, protected array $resources)
    {
    }

    /**
     * Get the search result.
     */
    public function get(): Collection
    {
        $result = new Collection([]);

        foreach ($this->resources as $resource) {
            if (count($resource->globalSearchColumns()) === 0) {
                continue;
            }

            $result->push([
                'title' => $resource->label(),
                'resource_name' => $resource::name(),
                'icon' => $resource::$icon,
                'action' => $resource::$globalSearchAction,
                'data' => $this->newQuery($resource)
                    ->take($resource::$globalSearchResultsLimit)
                    ->get()
                    ->whereInstanceOf(Presentable::class)
                    ->map(fn (Model&Presentable $model) => $this->data($model, $resource)),
            ]);
        }

        return $result;
    }

    /**
     * Get the query that should be used to perform global search.
     */
    protected function newQuery(Resource $resource): Builder
    {
        return $resource->globalSearchQuery($this->request);
    }

    /**
     * Get the model data for the response.
     */
    protected function data(Model&Presentable $model, Resource $resource): array
    {
        return [
            'id' => $model->getKey(),
            'path' => $model->path(),
            'display_name' => $model->displayName(),
            'created_at' => $model->created_at,
            'resourceName' => $resource->name(),
        ];
    }

    /**
     * Serialize GlobalSearch class.
     */
    public function jsonSerialize(): array
    {
        return $this->get()->all();
    }
}
