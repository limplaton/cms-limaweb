<?php
 

namespace Modules\Core\App\Contracts\Resources;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Table\Table;

interface Tableable
{
    /**
     * Provide the resource table class instance.
     */
    public function table(Builder $query, ResourceRequest $request): Table;
}
