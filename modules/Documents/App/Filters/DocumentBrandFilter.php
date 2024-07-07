<?php
 

namespace Modules\Documents\App\Filters;

use Modules\Brands\App\Models\Brand;
use Modules\Core\App\Filters\MultiSelect;

class DocumentBrandFilter extends MultiSelect
{
    /**
     * Create new DocumentBrandFilter instance
     */
    public function __construct()
    {
        parent::__construct('brand_id', __('documents::fields.documents.brand.name'));

        $this->labelKey('name')
            ->valueKey('id')
            ->options(
                fn () => Brand::select(['id', 'name'])
                    ->visible()
                    ->orderBy('is_default', 'desc')
                    ->orderBy('name')
                    ->get()
            );
    }
}
