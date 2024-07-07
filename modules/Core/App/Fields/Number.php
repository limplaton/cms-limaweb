<?php
 

namespace Modules\Core\App\Fields;

use Modules\Core\App\Contracts\Fields\Customfieldable;
use Modules\Core\App\Contracts\Fields\UniqueableCustomfield;

class Number extends Field implements Customfieldable, UniqueableCustomfield
{
    /**
     * Field component.
     */
    public static $component = 'number-field';

    /**
     * Initialize Numeric field
     *
     * @param  string  $attribute
     * @param  string|null  $label
     */
    public function __construct($attribute, $label = null)
    {
        parent::__construct($attribute, $label);

        $this->rules(['nullable', 'integer'])
            ->provideSampleValueUsing(fn () => rand(1990, date('Y')))
            ->useSearchColumn([$this->attribute => '=']);
    }

    /**
     * Create the custom field value column in database.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $table
     */
    public static function createValueColumn($table, string $fieldId): void
    {
        $table->integer($fieldId)->index()->nullable();
    }
}
