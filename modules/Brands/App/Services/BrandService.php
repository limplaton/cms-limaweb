<?php
 

namespace Modules\Brands\App\Services;

use Modules\Brands\App\Models\Brand;

class BrandService
{
    /**
     * Save new brand in storage.
     */
    public function create(array $attributes): Brand
    {
        $brand = Brand::create($attributes);

        $brand->saveVisibilityGroup($attributes['visibility_group'] ?? []);

        if ($brand->is_default === true) {
            $this->ensureNoOtherBrandIsDefaultThan($brand);
        }

        return $brand;
    }

    /**
     * Update the given brand in storage.
     */
    public function update(array $attributes, Brand $brand): Brand
    {
        $brand->fill($attributes)->save();

        if ($attributes['visibility_group'] ?? false) {
            $brand->saveVisibilityGroup($attributes['visibility_group']);
        }

        if ($brand->wasChanged('is_default') && $brand->is_default === true) {
            $this->ensureNoOtherBrandIsDefaultThan($brand);
        }

        return $brand;
    }

    /**
     * Ensure that no other brand is default than the given brand.
     */
    protected function ensureNoOtherBrandIsDefaultThan(Brand $brand): void
    {
        Brand::where('id', '!=', $brand->id)->update(['is_default' => false]);
    }
}
