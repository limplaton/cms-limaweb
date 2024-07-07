<?php
 

namespace Modules\Core\App\Resource;

use Modules\Core\App\Contracts\Presentable;
use Modules\Core\App\Models\Model;

class EmailSearch extends GlobalSearch
{
    /**
     * Provide the model data for the response.
     */
    protected function data(Model&Presentable $model, Resource $resource): array
    {
        return [
            'id' => $model->getKey(),
            'address' => $model->email,
            'name' => $model->displayName(),
            'path' => $model->path(),
            'resourceName' => $resource->name(),
        ];
    }
}
