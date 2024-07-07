<?php
 

namespace Modules\Core\App\Fields;

use Modules\Core\App\Contracts\Fields\Customfieldable;
use Modules\Core\App\Contracts\Fields\UniqueableCustomfield;

class Email extends Field implements Customfieldable, UniqueableCustomfield
{
    use ChecksForDuplicates;

    /**
     * Field component.
     */
    public static $component = 'email-field';

    /**
     * Initialize new Email instance class
     *
     * @param  string  $attribute  field attribute
     * @param  string|null  $label  field label
     */
    public function __construct($attribute, $label = null)
    {
        parent::__construct($attribute, $label);

        $this->rules(['nullable', 'email'])->provideSampleValueUsing(fn () => uniqid().'@example.com');
    }

    /**
     * Create the custom field value column in database.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $table
     */
    public static function createValueColumn($table, string $fieldId): void
    {
        $table->string($fieldId)->nullable();
    }
}
