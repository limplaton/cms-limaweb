<?php
 

namespace Modules\Core\App\Http\Controllers\Api\Resource;

use Illuminate\Validation\Rule;
use Modules\Core\App\Contracts\Fields\Dateable;
use Modules\Core\App\Contracts\Resources\Exportable;
use Modules\Core\App\Fields\Field;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends ApiController
{
    /**
     * Export resource data
     */
    public function handle(ResourceRequest $request): BinaryFileResponse
    {
        abort_unless($request->resource() instanceof Exportable, 404);

        $this->authorize('export', $request->resource()::$model);

        $availableFields = $request->resource()->fieldsForExport();

        $request->validate([
            'date_range_field' => [
                'sometimes',
                'nullable',
                Rule::in($availableFields->filter(
                    fn (Field $field) => $field instanceof Dateable)->pluck('attribute')
                )],
        ]);

        $filteredFields = $availableFields->when(is_array($request->input('fields')), function ($fields) use ($request) {
            return $fields->filter(fn (Field $field) => $field->isPrimary() || in_array($field->attribute, $request->fields));
        });

        $query = $request->resource()->exportQuery($request, $filteredFields);

        return $request->resource()
            ->exportable($query)
            ->setUser($request->user())
            ->setFields($filteredFields)
            ->download($request->type);
    }
}
