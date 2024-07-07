<?php
 

namespace Modules\Calls\App\Cards;

use Illuminate\Http\Request;
use Modules\Calls\App\Models\Call;
use Modules\Core\App\Charts\Progression;
use Modules\Users\App\Criteria\QueriesByUserCriteria;

class LoggedCallsByDay extends Progression
{
    /**
     * Calculates logged calls by day
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        $query = (new Call)->newQuery();

        if ($userId = $this->getUserId($request)) {
            $query->criteria(new QueriesByUserCriteria($userId));
        }

        return $this->countByDays($request, $query);
    }

    /**
     * Get the ranges available for the chart.
     */
    public function ranges(): array
    {
        return [
            7 => __('core::dates.periods.7_days'),
            15 => __('core::dates.periods.15_days'),
            30 => __('core::dates.periods.30_days'),
        ];
    }

    /**
     * The card name
     */
    public function name(): string
    {
        return __('calls::call.cards.by_day');
    }
}
