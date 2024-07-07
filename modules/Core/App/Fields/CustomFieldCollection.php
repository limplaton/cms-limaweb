<?php
 

namespace Modules\Core\App\Fields;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\App\Models\CustomField;

class CustomFieldCollection extends Collection
{
    /**
     * Get the optionable fields.
     *
     * @return static
     */
    public function optionable()
    {
        return $this->filter->isOptionable();
    }

    /**
     * Get the multi optionable fields.
     *
     * @return static
     */
    public function multiOptionable()
    {
        return $this->optionable()->filter->isMultiOptionable();
    }

    /**
     * Filter only unique custom fields from the collection.
     *
     * @return static
     */
    public function filterUnique()
    {
        return $this->filter->isUnique();
    }

    /**
     * Get the fillable attributes for the model
     *
     * @return array
     */
    public function fillable()
    {
        return $this->reject->isMultiOptionable()->pluck('field_id')->all();
    }

    /**
     * Get the model casts
     *
     * @return array
     */
    public function modelCasts()
    {
        $data = $this->castableFieldsData();

        return $this->castable()->mapWithKeys(function (CustomField $field) use ($data) {
            return [$field->field_id => $data[$field->field_type]];
        })->all();
    }

    /**
     * Get the castable fields
     *
     * @return static
     */
    public function castable()
    {
        return $this->whereIn('field_type', array_keys($this->castableFieldsData()));
    }

    /**
     * Get the castable fields data
     */
    protected function castableFieldsData(): array
    {
        return [
            'Text' => 'string',
            'Url' => 'string',
            'ColorSwatch' => 'string',
            'Textarea' => 'string',
            'Email' => 'string',
            'Timezone' => 'string',
            'Date' => 'date',
            'DateTime' => 'datetime',
            'Boolean' => 'boolean',
            'Numeric' => 'decimal:3',
            'Number' => 'int',
            'Radio' => 'int',
            'Select' => 'int',
        ];
    }
}
