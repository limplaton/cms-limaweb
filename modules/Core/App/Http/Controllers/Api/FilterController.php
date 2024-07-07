<?php
 

namespace Modules\Core\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Http\Requests\FilterRequest;
use Modules\Core\App\Http\Resources\FilterResource;
use Modules\Core\App\Models\Filter;

class FilterController extends ApiController
{
    /**
     * Get filters from storage by identifier for logged in user.
     */
    public function index(string $identifier, Request $request): JsonResponse
    {
        $userId = $request->user()->getKey();

        $filters = Filter::forUser($userId, $identifier)->get();

        return $this->response(
            FilterResource::collection($filters)
        );
    }

    /**
     * Create filter in storage.
     */
    public function store(FilterRequest $request): JsonResponse
    {
        $filter = new Filter($request->merge(['user_id' => $request->user()->id])->all());

        $filter->save();

        return $this->response(new FilterResource($filter), JsonResponse::HTTP_CREATED);
    }

    /**
     * Update given filter.
     */
    public function update(Filter $filter, FilterRequest $request): JsonResponse
    {
        $this->authorize('update', $filter);

        if ($filter->is_system_default) {
            abort(403, 'Application default filters cannot be updated.');
        } elseif ($filter->is_readonly) {
            abort(403, 'Readonly filters cannot be updated.');
        }

        $filter->fill($request->except(['user_id', 'identifier']))->save();

        return $this->response(new FilterResource($filter));
    }

    /**
     * Delete given filter.
     */
    public function destroy(Filter $filter): JsonResponse
    {
        $this->authorize('delete', $filter);

        if ($filter->is_system_default) {
            abort(403, 'Application default filters cannot be deleted.');
        } elseif ($filter->is_readonly) {
            abort(403, 'Readonly filters cannot be deleted.');
        }

        $filter->delete();

        return $this->response('', JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Mark the given filter as default for the given view.
     */
    public function markAsDefault(Filter $filter, string $view, Request $request): JsonResponse
    {
        $filter->markAsDefault($view, $request->user()->getKey())->loadMissing('defaults');

        return $this->response(new FilterResource($filter));
    }

    /**
     * Unmark the given filter as default from the given view.
     */
    public function unMarkAsDefault(Filter $filter, string $view, Request $request): JsonResponse
    {
        $filter->unMarkAsDefault($view, $request->user()->getKey())->loadMissing('defaults');

        return $this->response(new FilterResource($filter));
    }
}
