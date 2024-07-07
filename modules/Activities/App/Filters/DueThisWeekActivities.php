<?php
 

namespace Modules\Activities\App\Filters;

use Modules\Activities\App\Models\Activity;
use Modules\Core\App\Filters\Filter;
use Modules\Core\App\Support\ProvidesBetweenArgumentsViaString;

class DueThisWeekActivities extends Filter
{
    use ProvidesBetweenArgumentsViaString;

    /**
     * Initialize DueThisWeekActivities class
     */
    public function __construct()
    {
        parent::__construct('due_this_week', __('activities::activity.filters.due_this_week'));

        $this->asStatic()->query(function ($builder, $value, $condition) {
            return $builder->where(function ($builder) {
                return $builder->whereBetween(
                    Activity::dueDateQueryExpression(),
                    $this->getBetweenArguments('this_week')
                )->incomplete();
            }, null, null, $condition);
        });
    }
}
