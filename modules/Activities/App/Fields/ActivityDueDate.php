<?php
 

namespace Modules\Activities\App\Fields;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Modules\Activities\App\Models\Activity;
use Modules\Core\App\Common\Placeholders\DatePlaceholder;
use Modules\Core\App\Common\Placeholders\DateTimePlaceholder;
use Modules\Core\App\Fields\Date;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Table\Column;

class ActivityDueDate extends Date
{
    /**
     * The model attribute that hold the time
     */
    protected string $timeField = 'due_time';

    /**
     * The model attribute that holds the date
     */
    protected string $dateField = 'due_date';

    /**
     * The inline edit popover width (medium|large).
     */
    public string $inlineEditPanelWidth = 'large';

    /**
     * Field component.
     */
    public static $component = 'activity-due-date-field';

    /**
     * Initialize new ActivityDueDate instance class
     */
    public function __construct($label)
    {
        parent::__construct($this->dateField, $label);

        $this->tapIndexColumn(function (Column $column) {
            $queryExpression = Activity::dateTimeExpression($this->dateField, $this->timeField, $this->dateField);

            $column
                ->queryAs($queryExpression)
                ->orderByUsing(function (Builder $query, string $direction) {
                    return $query->orderBy(
                        Activity::dateTimeExpression($this->dateField, $this->timeField),
                        $direction
                    );
                })
                ->fillRowDataUsing(function (array &$row, Activity $model) {
                    $row[$this->dateField] = $this->hasTime($model->{$this->dateField}) ?
                        Carbon::parse($model->{$this->dateField}) :
                        $model->{$this->dateField};
                });
        })
            ->hideLabel()
            ->displayUsing(function ($model, $value) {
                $date = $model->{$this->dateField};
                $time = $model->{$this->timeField};
                $dateCarbon = Carbon::parse($date);
                $user = $model->user;

                return with($time ? Carbon::parse(
                    $dateCarbon->format('Y-m-d').' '.$time
                )->format('H:i') : null, function ($time) use ($dateCarbon, $user) {
                    if ($time) {
                        return Carbon::parse($dateCarbon->format('Y-m-d').' '.$time.':00')->formatDateTimeForUser($user);
                    }

                    return Carbon::parse($dateCarbon->format('Y-m-d'))->formatDateForUser($user);
                });
            })
            ->provideSampleValueUsing(fn () => date('Y-m-d').' 08:00:00')
            ->fillUsing(function (Model $model, string $attribute, ResourceRequest $request, mixed $value, string $requestAttribute) {
                $dateTime = $this->ensureInAppTimezone(Carbon::parse($value));

                $model->{$this->dateField} = $dateTime->format('Y-m-d');
                $model->{$this->timeField} = $this->hasTime($value) ? $dateTime->format('H:i:00') : null;
            })
            ->resolveForJsonResourceUsing(function (Model $model, string $attribute) {
                return [
                    $attribute => $model->due_time ? Carbon::parse($model->fullDueDate)->toJSON() : $model->due_date,
                ];
            });
    }

    /**
     * Convert the given Carbon instance to the application timezone.
     */
    protected function ensureInAppTimezone(Carbon $date): Carbon
    {
        $appTimezone = config('app.timezone');

        if (strtolower($date->timezone->getName()) !== strtolower($appTimezone)) {
            $date->setTimezone($appTimezone);
        }

        return $date;
    }

    /**
     * Check whether the given date string has time.
     */
    protected function hasTime(string $date): bool
    {
        return (bool) preg_match('/(\d{2}:\d{2}:\d{2})|(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})/', $date);
    }

    /**
     * Resolve the field value for export
     *
     * @param  \Modules\Core\App\Models\Model  $model
     * @return string|null
     */
    public function resolveForExport($model)
    {
        if (is_callable($this->exportCallback)) {
            return call_user_func_array($this->exportCallback, [$model, $this->resolve($model), $this->attribute]);
        }

        $time = $this->getTimeValue($model);

        $carbonInstance = $this->dateTimeToCarbon($model->{$this->dateField}, $time);

        return $carbonInstance->format('Y-m-d'.($time ? ' H:i:s' : ''));
    }

    /**
     * Get the mailable template placeholder
     *
     * @param  \Modules\Core\App\Models\Model|null  $model
     * @return \Modules\Core\App\Common\Placeholders\DatePlaceholder|\Modules\Core\App\Common\Placeholders\DateTimePlaceholder
     */
    public function mailableTemplatePlaceholder($model)
    {
        $placeholderClass = $model?->{$this->timeField} ?
            DateTimePlaceholder::class :
            DatePlaceholder::class;

        return $placeholderClass::make($this->attribute)
            ->formatUsing(fn () => $this->resolveForDisplay($model))
            ->description($this->label);
    }

    /**
     * Get the time value from the model
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return string|null
     */
    protected function getTimeValue($model)
    {
        if (! $model->{$this->timeField}) {
            return null;
        }

        return $this->dateTimeToCarbon(
            $this->resolve($model),
            $model->{$this->timeField}
        )->format('H:i');
    }

    /**
     * Create Carbon UTC instance from the given date and time
     *
     * @param  string  $date
     * @param  string|null  $time
     * @return \Carbon\Carbon
     */
    protected function dateTimeToCarbon($date, $time)
    {
        return Carbon::parse(
            Carbon::parse($date)->format('Y-m-d').($time ? ' '.$time : '')
        );
    }
}
