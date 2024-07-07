<?php
 

namespace Modules\Core\App\Http\Requests;

use Modules\Core\App\Contracts\Resources\Tableable;
use Modules\Core\App\Resource\Resource;
use Modules\Core\App\Table\Table;

class ResourceTableRequest extends ResourceRequest
{
    /**
     * Get the class of the resource being requested.
     */
    public function resource(): Resource
    {
        return tap(parent::resource(), function ($resource) {
            abort_if(! $resource instanceof Tableable, 404);
        });
    }

    /**
     * Resolve the resource table for the current request
     */
    public function resolveTable(): Table
    {
        return $this->resource()->resolveTable($this);
    }

    /**
     * Resolve the resource trashed table for the current request
     */
    public function resolveTrashedTable(): Table
    {
        return $this->resource()->resolveTrashedTable($this);
    }
}
