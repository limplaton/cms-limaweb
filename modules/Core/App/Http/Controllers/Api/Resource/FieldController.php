<?php
 

namespace Modules\Core\App\Http\Controllers\Api\Resource;

use Illuminate\Http\JsonResponse;
use Modules\Core\App\Contracts\Resources\Exportable;
use Modules\Core\App\Facades\Fields;
use Modules\Core\App\Fields\Field;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Http\Requests\ResourceRequest;

class FieldController extends ApiController
{
    /**
     * Get the resource index fields.
     */
    public function index(ResourceRequest $request): JsonResponse
    {
        // In case the field should be used for inline edit.
        if ($resourceId = $request->integer('resource_id')) {
            $request->setResourceId($resourceId)
                ->resource()
                ->setModel($request->record());
        }

        return $this->response(
            $request->resource()->fieldsForIndex()->each(function (Field $field) use ($request) {
                Fields::applyCustomizedAttributes($field, $request->resourceName(), Fields::UPDATE_VIEW);
            })
        );
    }

    /**
     * Get the resource create fields.
     */
    public function create(ResourceRequest $request): JsonResponse
    {
        return $this->response(
            $request->resource()->visibleFieldsForCreate()
        );
    }

    /**
     * Get the resource update fields.
     */
    public function update(ResourceRequest $request): JsonResponse
    {
        $request->resource()->setModel($request->record());

        return $this->response(
            $request->resource()->visibleFieldsForUpdate()
        );
    }

    /**
     * Get the resource detail fields.
     */
    public function detail(ResourceRequest $request): JsonResponse
    {
        abort_unless($request->resource()::$hasDetailView, 404);

        $request->resource()->setModel($request->record());

        return $this->response(
            $request->resource()->visibleFieldsForDetail()->each(function (Field $field) use ($request) {
                $field->withMeta([
                    'inlineEditDisabled' => $field->isInlineEditDisabled($request->record()),
                ]);
            })
        );
    }

    /**
     * Get the resource export fields.
     */
    public function export(ResourceRequest $request): JsonResponse
    {
        abort_unless($request->resource() instanceof Exportable, 404);

        return $this->response(
            $request->resource()->fieldsForExport()
        );
    }
}
