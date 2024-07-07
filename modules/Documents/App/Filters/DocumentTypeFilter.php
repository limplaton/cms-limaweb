<?php
 

namespace Modules\Documents\App\Filters;

use Modules\Core\App\Filters\MultiSelect;
use Modules\Documents\App\Models\DocumentType;

class DocumentTypeFilter extends MultiSelect
{
    /**
     * Create new DocumentTypeFilter instance
     */
    public function __construct()
    {
        parent::__construct('document_type_id', __('documents::fields.documents.type.name'));

        $this->labelKey('name')
            ->valueKey('id')
            ->options(
                fn () => DocumentType::select(['id', 'name'])
                    ->visible()
                    ->orderBy('name')
                    ->get()
            );
    }
}
