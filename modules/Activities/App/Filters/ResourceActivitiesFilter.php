<?php
 

namespace Modules\Activities\App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Modules\Activities\App\Models\Activity;
use Modules\Core\App\Filters\Select;
use Modules\Core\App\QueryBuilder\Parser;
use Modules\Core\App\Support\ProvidesBetweenArgumentsViaString;

class ResourceActivitiesFilter extends Select
{
    use ProvidesBetweenArgumentsViaString;

    /**
     * Initialize ResourceActivitiesFilter class
     */
    public function __construct()
    {
        parent::__construct('activities', __('activities::activity.activity'), ['equal']);

        $this->options([
            'today' => __('core::dates.due.today'),
            'next_day' => __('core::dates.due.tomorrow'),
            'this_week' => __('core::dates.due.this_week'),
            'next_week' => __('core::dates.due.next_week'),
            'this_month' => __('core::dates.due.this_month'),
            'next_month' => __('core::dates.due.next_month'),
            'this_quarter' => __('core::dates.due.this_quarter'),
            'overdue' => __('activities::activity.overdue'),
            'doesnt_have_activities' => __('activities::activity.doesnt_have_activities'),
        ])->displayAs([
            __('activities::activity.filters.display.has'),
            'overdue' => __('activities::activity.filters.display.overdue'),
            'doesnt_have_activities' => __('activities::activity.filters.display.doesnt_have_activities'),
        ]);
    }

    /**
     * Apply the filter when custom query callback is provided
     *
     * @param  mixed  $value
     * @param  string  $condition
     * @param  array  $sqlOperator
     * @param  stdClass  $rule
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Builder $builder, $value, $condition, $sqlOperator, $rule, Parser $parser)
    {
        if ($value == 'doesnt_have_activities') {
            return $builder->doesntHave('activities', $condition);
        }

        return $builder->has('activities', '>=', 1, $condition, function ($query) use ($value) {
            if ($value === 'overdue') {
                return $query->overdue();
            }

            return $query->whereBetween(Activity::dueDateQueryExpression(), $this->getBetweenArguments($value));
        });
    }

    /**
     * Check whether the filter has custom callback
     */
    public function hasCustomQuery(): bool
    {
        return true;
    }
}
