<?php
 

namespace Modules\Core\App\Fields;

use Modules\Core\App\Contracts\Fields\Customfieldable;

class Radio extends Optionable implements Customfieldable
{
    /**
     * Field component.
     */
    public static $component = 'radio-field';

    /**
     * Indicates that the radio field will be inline
     */
    public function inline(): static
    {
        $this->withMeta(['inline' => true]);

        return $this;
    }

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
