<?php
 

namespace Modules\Core\App\Fields;

class MultiSelect extends Select
{
    /**
     * Field component.
     */
    public static $component = 'select-multiple-field';

    /**
     * Create the custom field value column in database.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $table
     */
    public static function createValueColumn($table, string $fieldId): void
    {
        //
    }
}
