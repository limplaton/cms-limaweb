<?php
 

namespace Modules\Core\App\Fields;

use Modules\Core\App\Contracts\Fields\Customfieldable;

class Checkbox extends Optionable implements Customfieldable
{
    /**
     * Field component.
     */
    public static $component = 'checkbox-field';

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
