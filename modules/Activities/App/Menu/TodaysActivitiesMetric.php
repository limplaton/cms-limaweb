<?php
 

namespace Modules\Activities\App\Menu;

use Modules\Activities\App\Criteria\ViewAuthorizedActivitiesCriteria;
use Modules\Activities\App\Models\Activity;
use Modules\Core\App\Menu\Metric;
use Modules\Core\App\Models\Filter;

class TodaysActivitiesMetric extends Metric
{
    /**
     * Get the metric name
     */
    public function name(): string
    {
        return __('activities::activity.metrics.todays');
    }

    /**
     * Get the metric count
     */
    public function count(): int
    {
        return Activity::dueToday()->criteria(ViewAuthorizedActivitiesCriteria::class)->count();
    }

    /**
     * Get the background color variant when the metric count is bigger then zero
     */
    public function backgroundColorVariant(): string
    {
        return 'warning';
    }

    /**
     * Get the front-end route that the highly will redirect to
     */
    public function route(): array|string
    {
        $filter = Filter::findByFlag('due-today-activities');

        return [
            'name' => 'activity-index',
            'query' => [
                'filter_id' => $filter?->id,
            ],
        ];
    }
}
