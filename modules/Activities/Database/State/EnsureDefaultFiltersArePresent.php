<?php
 

namespace Modules\Activities\Database\State;

use Illuminate\Support\Facades\DB;
use Modules\Activities\App\Filters\DueThisWeekActivities;
use Modules\Activities\App\Filters\DueTodayActivities;
use Modules\Activities\App\Filters\OpenActivities;
use Modules\Activities\App\Filters\OverdueActivities;
use Modules\Core\App\Models\Filter;

class EnsureDefaultFiltersArePresent
{
    public function __invoke()
    {
        if (DB::table('filters')->where('flag', 'open-activities')->count() === 0) {
            $this->newModelInstance([
                'identifier' => 'activities',
                'name' => 'activities::activity.filters.open',
                'flag' => 'open-activities',
                'rules' => [
                    OpenActivities::make()->toArray(),
                ],
            ])->save();
        }

        if (DB::table('filters')->where('flag', 'due-today-activities')->count() === 0) {
            $this->newModelInstance([
                'identifier' => 'activities',
                'name' => 'activities::activity.filters.due_today',
                'flag' => 'due-today-activities',
                'rules' => [
                    DueTodayActivities::make()->toArray(),
                ],
            ])->save();
        }

        if (DB::table('filters')->where('flag', 'due-this-week-activities')->count() === 0) {
            $this->newModelInstance([
                'identifier' => 'activities',
                'name' => 'activities::activity.filters.due_this_week',
                'flag' => 'due-this-week-activities',
                'rules' => [
                    DueThisWeekActivities::make()->toArray(),
                ],
            ])->save();
        }

        if (DB::table('filters')->where('flag', 'overdue-activities')->count() === 0) {
            $this->newModelInstance([
                'identifier' => 'activities',
                'name' => 'activities::activity.overdue',
                'flag' => 'overdue-activities',
                'rules' => [
                    OverdueActivities::make()->setOperator('equal')->setValue('yes')->toArray(),
                ],
            ])->save();
        }
    }

    protected function newModelInstance($attributes)
    {
        return new Filter(array_merge([
            'is_shared' => true,
            'is_readonly' => true,
        ], $attributes));
    }
}
