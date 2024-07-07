<?php
 

namespace Modules\Activities\App\Filters;

use Modules\Core\App\Filters\Radio;

class OverdueActivities extends Radio
{
    /**
     * Create new instance of OverdueActivities class
     */
    public function __construct()
    {
        parent::__construct('overdue', __('activities::activity.overdue'));

        $this->options(['yes' => __('core::app.yes'), 'no' => __('core::app.no')])
            ->query(function ($builder, $value, $condition) {
                return $builder->where(fn ($builder) => $builder->overdue($value === 'yes' ? '<=' : '>'), null, null, $condition);
            });
    }
}
