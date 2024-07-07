<?php
 

namespace Modules\Core\App\Fields;

use Modules\Core\App\Contracts\Fields\Customfieldable;
use Modules\Core\App\Contracts\Fields\UniqueableCustomfield;

class Text extends Field implements Customfieldable, UniqueableCustomfield
{
    use ChecksForDuplicates;

    /**
     * Input type
     */
    public string $inputType = 'text';

    /**
     * Field component.
     */
    public static $component = 'text-field';

    /**
     * Specify type attribute for the text field
     */
    public function inputType(string $type): static
    {
        $this->inputType = $type;

        return $this;
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

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'inputType' => $this->inputType,
        ]);
    }
}
