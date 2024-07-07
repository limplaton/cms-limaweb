<?php
 

namespace Modules\Core\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TableResource extends JsonResource
{
    /**
     * Transform the table into an array.
     *
     * Uses Json resource create a proper API pagination formatting
     */
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
