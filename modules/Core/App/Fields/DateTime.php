<?php
 

namespace Modules\Core\App\Fields;

use Modules\Core\App\Common\Placeholders\DateTimePlaceholder;
use Modules\Core\App\Contracts\Fields\Customfieldable;
use Modules\Core\App\Contracts\Fields\Dateable;
use Modules\Core\App\Fields\Dateable as DateableTrait;
use Modules\Core\App\Support\Carbon;
use Modules\Core\App\Table\DateTimeColumn;

class DateTime extends Field implements Customfieldable, Dateable
{
    use DateableTrait;

    /**
     * Field component.
     */
    public static $component = 'date-time-field';

    /**
     * Indicates if the field is searchable.
     */
    protected bool $searchable = false;

    /**
     * Initialize new DateTime instance class
     *
     * @param  string  $attribute  field attribute
     * @param  string|null  $label  field label
     */
    public function __construct($attribute, $label = null)
    {
        parent::__construct($attribute, $label);

        $this->rules(['nullable', 'date'])
            ->provideSampleValueUsing(fn () => date('Y-m-d H:i:s'))
            ->displayUsing(
                fn ($model, $value) => $value ? Carbon::parse($value)->formatDateTimeForUser() : null
            );
    }

    /**
     * Create the custom field value column in database.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $table
     */
    public static function createValueColumn($table, string $fieldId): void
    {
        $table->dateTime($fieldId)->nullable();
    }

    /**
     * Get the mailable template placeholder
     *
     * @param  \Modules\Core\App\Models\Model|null  $model
     * @return \Modules\Core\App\Common\Placeholders\DateTimePlaceholder
     */
    public function mailableTemplatePlaceholder($model)
    {
        return DateTimePlaceholder::make($this->attribute)
            ->value(fn () => $this->resolve($model))
            ->forUser($model?->user)
            ->description($this->label);
    }

    /**
     * Provide the column used for index
     */
    public function indexColumn(): DateTimeColumn
    {
        return new DateTimeColumn($this->attribute, $this->label);
    }
}
