<?php
 

namespace Modules\Core\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\App\Fields\CustomFieldService;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Http\Requests\CustomFieldRequest;
use Modules\Core\App\Http\Resources\CustomFieldResource;
use Modules\Core\App\Models\CustomField;

class CustomFieldController extends ApiController
{
    /**
     * Get the fields types that can be used as custom fields.
     */
    public function index(Request $request): JsonResponse
    {
        $fields = CustomField::with('options')
            ->latest()
            ->paginate($request->integer('per_page') ?: null);

        return $this->response(
            CustomFieldResource::collection($fields)
        );
    }

    /**
     * Create new custom field.
     */
    public function store(CustomFieldRequest $request, CustomFieldService $service): JsonResponse
    {
        $field = $service->create($request->all());

        return $this->response(new CustomFieldResource($field), JsonResponse::HTTP_CREATED);
    }

    /**
     * Update custom field.
     */
    public function update(string $id, CustomFieldRequest $request, CustomFieldService $service): JsonResponse
    {
        $field = $service->update($request->except(['field_type', 'field_id', 'resource_name']), (int) $id);

        return $this->response(new CustomFieldResource($field));
    }

    /**
     * Delete custom field.
     */
    public function destroy(string $id, CustomFieldService $service): JsonResponse
    {
        $service->delete((int) $id);

        return $this->response('', JsonResponse::HTTP_NO_CONTENT);
    }
}
