<?php
 

namespace Modules\Core\App\Fields;

use Modules\Core\App\Table\Column;

class ID extends Field
{
    /**
     * Field component.
     */
    public static $component = 'id-field';

    /**
     * Initialize new ID instance.
     */
    public function __construct(string $attribute = 'id', ?string $label = null)
    {
        parent::__construct($attribute, $label ?: __('core::app.id'));

        $this->exceptOnForms()
            ->readOnly(true)
            ->useSearchColumn([$this->attribute => '='])
            ->tapIndexColumn(fn (Column $column) => $column
                ->width('100px')
                ->minWidth('100px')
                ->centered()
            );
    }
}
