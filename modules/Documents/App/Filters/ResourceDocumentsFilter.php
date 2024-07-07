<?php
 

namespace Modules\Documents\App\Filters;

use Modules\Core\App\Filters\HasFilter;
use Modules\Core\App\Filters\Number;
use Modules\Core\App\Filters\Numeric;
use Modules\Core\App\Filters\Operand;
use Modules\Core\App\Filters\Text;

class ResourceDocumentsFilter extends HasFilter
{
    /**
     * Initialize ResourceDocumentsFilter class
     *
     * @param  string  $singularLabel
     */
    public function __construct()
    {
        parent::__construct('documents', __('documents::document.document'));

        $this->setOperands([
            Operand::from(Numeric::make('amount', __('documents::fields.documents.amount'))),
            Operand::from(DocumentStatusFilter::make()),
            Operand::from(DocumentTypeFilter::make()),
            Operand::from(DocumentBrandFilter::make()),
            Operand::from(Text::make('name', __('documents::document.title'))),
            Operand::from(Number::make('total_count', __('documents::document.total_documents'))->countableRelation('documents')),
        ]);
    }
}
