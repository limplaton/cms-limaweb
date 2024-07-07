<?php
 

namespace Modules\Core\App\Fields;

use Illuminate\Support\Arr;
use Modules\Core\App\Contracts\Fields\Customfieldable;
use Modules\Core\App\Facades\Timezone as Facade;

class Timezone extends Field implements Customfieldable
{
    /**
     * Field component.
     */
    public static $component = 'timezone-field';

    /**
     * Initialize Timezone field
     *
     * @param  string  $attribute
     * @param  string|null  $label
     */
    public function __construct($attribute, $label = null)
    {
        parent::__construct($attribute, $label ?? __('core::app.timezone'));

        $this->rules(['nullable', 'timezone:all'])
            ->provideSampleValueUsing(fn () => Arr::random(tz()->all()));
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
     * Provide the options intended for Zapier
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'timezones' => Facade::toArray(),
        ]);
    }
}
