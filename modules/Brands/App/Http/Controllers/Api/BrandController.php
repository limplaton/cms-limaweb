<?php
 

namespace Modules\Brands\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Brands\App\Http\Requests\BrandRequest;
use Modules\Brands\App\Http\Resources\BrandResource;
use Modules\Brands\App\Models\Brand;
use Modules\Brands\App\Services\BrandService;
use Modules\Core\App\Http\Controllers\ApiController;

class BrandController extends ApiController
{
    /**
     * Display a listing of the company brands.
     */
    public function index(): JsonResponse
    {
        $brands = Brand::visible()
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return $this->response(BrandResource::collection($brands));
    }

    /**
     * Display the specified company brand.
     */
    public function show(Brand $brand, Request $request): JsonResponse
    {
        $this->authorize('view', $brand);

        $brand->loadMissing($request->getWith());

        return $this->response(new BrandResource($brand));
    }

    /**
     * Store a newly created company brand in storage.
     */
    public function store(BrandRequest $request, BrandService $service): JsonResponse
    {
        $this->authorize('create', Brand::class);

        $brand = $service->create($request->input());

        return $this->response(
            new BrandResource($brand),
            JsonResponse::HTTP_CREATED
        );
    }

    /**
     * Update the specified company brand in storage.
     */
    public function update(Brand $brand, BrandRequest $request, BrandService $service): JsonResponse
    {
        $this->authorize('update', $brand);

        $brand = $service->update($request->input(), $brand);

        $brand->loadMissing($request->getWith());

        return $this->response(
            new BrandResource($brand)
        );
    }

    /**
     * Remove the specified company brand from storage.
     */
    public function destroy(Brand $brand): JsonResponse
    {
        if (Brand::count() === 1) {
            abort(409, 'There must be at least one brand.');
        }

        $this->authorize('delete', $brand);

        $brand->delete();

        return $this->response('', JsonResponse::HTTP_NO_CONTENT);
    }
}
