<?php
 

namespace Modules\Activities\App\Filters;

use Modules\Core\App\Filters\Filter;
use Modules\Core\App\Support\ProvidesBetweenArgumentsViaString;

class DueTodayActivities extends Filter
{
    use ProvidesBetweenArgumentsViaString;

    /**
     * Initialize DueTodayActivities class
     */
    public function __construct()
    {
        parent::__construct('due_today', __('activities::activity.filters.due_today'));

        $this->asStatic()->query(function ($builder, $value, $condition) {
            return $builder->where(function ($builder) {
                $builder->dueToday();
            }, null, null, $condition);
        });
    }
}
