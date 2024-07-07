<?php
 

namespace Modules\Core\App\Http\Requests;

use Modules\Core\App\Fields\FieldsCollection;

class UpdateResourceRequest extends ResourceRequest
{
    use InteractsWithResourceFields;

    /**
     * Get the fields for the current request.
     */
    public function fields(): FieldsCollection
    {
        $this->resource()->setModel($this->record());

        return $this->resource()->fieldsForUpdate()->withoutReadonly();
    }
}
