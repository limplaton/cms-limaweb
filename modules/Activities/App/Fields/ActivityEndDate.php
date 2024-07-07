<?php
 

namespace Modules\Activities\App\Fields;

use Closure;
use Illuminate\Support\Carbon;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Models\Model;

class ActivityEndDate extends ActivityDueDate
{
    /**
     * The model attribute that holds the time
     */
    protected string $dateField = 'end_date';

    /**
     * The model attribute that holds the date
     */
    protected string $timeField = 'end_time';

    /**
     * Field component.
     */
    public static $component = 'activity-end-date-field';

    /**
     * Initialize new ActivityEndDate instance
     *
     * @param  string  $label
     */
    public function __construct($label)
    {
        parent::__construct($label);

        $this->resolveForJsonResourceUsing(function (Model $model, string $attribute) {
            return [
                $attribute => $model->end_time ? Carbon::parse($model->fullEndDate)->toJSON() : $model->end_date,
            ];
        })->rules(function (string $attribute, mixed $value, Closure $fail, ResourceRequest $request) {
            if (empty($value)) {
                return;
            }

            $dueDate = $request->input('due_date');
            $endDate = $request->input('end_date');

            $hasEndTime = $this->hasTime($endDate);
            $hasDueTime = $this->hasTime($dueDate);

            $dueCarbon = $this->ensureInAppTimezone(Carbon::parse($dueDate));
            $endCarbon = $this->ensureInAppTimezone(Carbon::parse($endDate));

            // When all day or both due_date and due_time has time, we will
            // compare the dates directly as it's approriate
            if ((! $hasEndTime && ! $hasDueTime) || ($hasEndTime && $hasDueTime)) {
                if ($endCarbon->lessThan($dueCarbon)) {
                    $fail('activities::activity.validation.end_date.less_than_due')->translate();
                }
            } elseif (! $hasEndTime && $hasDueTime) {
                // To compare a date without a time to a date with a time, we'll add the time from the due date to the end date.
                // This ensures accurate comparison between the two dates.
                $endCarbon->hour($dueCarbon->hour)
                    ->minute($dueCarbon->minute)
                    ->second($dueCarbon->second);

                if ($endCarbon->lessThan($dueCarbon)) {
                    $fail('activities::activity.validation.end_date.less_than_due')->translate();
                }

                if (! $endCarbon->isSameDay($dueCarbon)) {
                    $fail('activities::activity.validation.end_time.required_when_end_date_is_in_future')->translate();
                }
            }
        });
    }
}
