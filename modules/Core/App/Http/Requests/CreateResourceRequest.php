<?php
 

namespace Modules\Core\App\Http\Requests;

use Modules\Core\App\Fields\FieldsCollection;

class CreateResourceRequest extends ResourceRequest
{
    use InteractsWithResourceFields;

    /**
     * Get the fields for the current request.
     */
    public function fields(): FieldsCollection
    {
        return $this->resource()->fieldsForCreate()->withoutReadonly();
    }
}
