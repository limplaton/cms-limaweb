<?php
 

namespace Modules\Activities\App\Filters;

use Modules\Core\App\Filters\Filter;

class OpenActivities extends Filter
{
    /**
     * Initialize OpenActivities Class
     */
    public function __construct()
    {
        parent::__construct('open_activities', __('activities::activity.filters.open'));

        $this->asStatic()->query(function ($builder, $value, $condition) {
            return $builder->where(fn ($builder) => $builder->incomplete(), null, null, $condition);
        });
    }
}
