<?php
 

namespace Modules\Brands\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Brands\App\Models\Brand;
use Modules\Brands\App\Services\BrandLogoService;
use Modules\Core\App\Http\Controllers\ApiController;

class BrandLogoController extends ApiController
{
    /**
     * Upload the given brand logo.
     */
    public function store(Brand $brand, string $type, Request $request, BrandLogoService $service): JsonResponse
    {
        $this->authorize('update', $brand);

        $request->validate([
            'logo_'.$type => 'required|image|max:1024',
        ]);

        $brand = $service->store($request->file('logo_'.$type), $brand, $type);

        return $this->response([
            'path' => $brand->{'logo_'.$type},
            'url' => $brand->{'logo'.ucfirst($type).'Url'},
        ]);
    }

    /**
     * Remove the specified brand logo.
     */
    public function delete(Brand $brand, string $type, BrandLogoService $service): void
    {
        $this->authorize('update', $brand);

        $service->delete($brand, $type);

        $brand->{'logo_'.$type} = null;
        $brand->save();
    }
}
