<?php
 

namespace Modules\Core\App\Fields;

use Illuminate\Support\Arr;
use Modules\Core\App\Contracts\Fields\Customfieldable;
use Modules\Core\App\Table\Column;

class Boolean extends Field implements Customfieldable
{
    /**
     * Field component.
     */
    public static $component = 'boolean-field';

    /**
     * Checkbox checked value
     */
    public bool|int|string $trueValue = true;

    /**
     * Checkbox unchecked value
     */
    public bool|int|string $falseValue = false;

    /**
     * Initialize new Boolean instance.
     */
    public function __construct()
    {
        parent::__construct(...func_get_args());

        $this->provideSampleValueUsing(fn () => filter_var($this->trueValue, FILTER_VALIDATE_BOOLEAN) ?
            Arr::random([$this->trueValue, 1, 'true']) :
            $this->trueValue
        )->useSearchColumn([$this->attribute => '=']);
    }

    /**
     * Get the field index column.
     */
    public function indexColumn(): Column
    {
        return parent::indexColumn()->centered();
    }

    /**
     * Checkbox checked value
     */
    public function trueValue(bool|int|string $val): static
    {
        $this->trueValue = $val;

        return $this;
    }

    /**
     * Checkbox unchecked value
     */
    public function falseValue(bool|int|string $val): static
    {
        $this->falseValue = $val;

        return $this;
    }

    /**
     * Resolve the field value for export
     * The export value should be in the original database value
     * not e.q. Yes or No
     *
     * @param  \Modules\Core\App\Models\Model  $model
     * @return string|null
     */
    public function resolveForExport($model)
    {
        if (is_callable($this->exportCallback)) {
            return call_user_func_array($this->exportCallback, [$model, $this->resolve($model), $this->attribute]);
        }

        return $this->resolve($model);
    }

    /**
     * Resolve the displayable field value
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return string|null
     */
    public function resolveForDisplay($model)
    {
        $value = parent::resolveForDisplay($model);

        return $value === $this->trueValue ? __('core::app.yes') : __('core::app.no');
    }

    /**
     * Create the custom field value column in database.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $table
     */
    public static function createValueColumn($table, string $fieldId): void
    {
        $table->boolean($fieldId)->nullable()->default(false);
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'trueValue' => $this->trueValue,
            'falseValue' => $this->falseValue,
        ]);
    }
}
