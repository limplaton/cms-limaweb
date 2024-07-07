<?php
 

namespace Modules\Core\App\Http\Requests;

use Illuminate\Database\Eloquent\Builder;

class TrashedResourceRequest extends ResourceRequest
{
    /**
     * Get new query for the current resource.
     */
    public function newQuery(): Builder
    {
        return $this->resource()->newTrashedQuery();
    }
}
