<?php
 

namespace Modules\Core\App\Contracts\Fields;

interface Customfieldable
{
    /**
     * Create the custom field value column in database.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $table
     */
    public static function createValueColumn($table, string $fieldId): void;
}
