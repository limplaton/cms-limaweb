<?php
 

namespace Modules\Core\App\Filters;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\App\Models\Country as CountryModel;

class Country extends Select
{
    /**
     * Initialize new Country filter.
     */
    public function __construct()
    {
        parent::__construct('country_id', __('core::filters.country'));

        $this->valueKey('id')->labelKey('name')->options($this->countries(...));
    }

    /**
     * Get the filter available countries.
     */
    public function countries(): Collection
    {
        return CountryModel::get(['id', 'name']);
    }
}
