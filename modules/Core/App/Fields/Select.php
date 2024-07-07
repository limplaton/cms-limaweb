<?php
 

namespace Modules\Core\App\Fields;

use Modules\Core\App\Contracts\Fields\Customfieldable;

class Select extends Optionable implements Customfieldable
{
    use Selectable;

    /**
     * Field component.
     */
    public static $component = 'select-field';

    /**
     * Create the custom field value column in database.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $table
     */
    public static function createValueColumn($table, string $fieldId): void
    {
        $table->unsignedBigInteger($fieldId)->nullable();
        $table->foreign($fieldId)
            ->references('id')
            ->on('custom_field_options');
    }
}
