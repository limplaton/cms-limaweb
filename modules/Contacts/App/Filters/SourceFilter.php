<?php
 

namespace Modules\Contacts\App\Filters;

use Modules\Contacts\App\Models\Source as SourceModel;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Filters\Select;

class SourceFilter extends Select
{
    /**
     * Initialize Source class
     */
    public function __construct()
    {
        parent::__construct('source_id', __('contacts::fields.companies.source.name'));

        $this->valueKey('id')
            ->labelKey('name')
            ->options(
                Innoclapps::resourceByModel(SourceModel::class)
            );
    }
}
