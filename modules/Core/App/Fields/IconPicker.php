<?php
 

namespace Modules\Core\App\Fields;

class IconPicker extends Field
{
    /**
     * Field component.
     */
    public static $component = 'icon-picker-field';

    /**
     * Indicates if the field is searchable.
     */
    protected bool $searchable = false;
}
