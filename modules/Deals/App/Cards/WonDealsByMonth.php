<?php
 

namespace Modules\Deals\App\Cards;

use Illuminate\Http\Request;
use Modules\Core\App\Charts\Progression;
use Modules\Deals\App\Criteria\ViewAuthorizedDealsCriteria;
use Modules\Deals\App\Models\Deal;
use Modules\Users\App\Criteria\QueriesByUserCriteria;

class WonDealsByMonth extends Progression
{
    /**
     * Calculates won deals by day
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        $query = Deal::won()->criteria(ViewAuthorizedDealsCriteria::class);

        if ($userId = $this->getUserId($request)) {
            $query->criteria(new QueriesByUserCriteria($userId));
        }

        return $this->countByMonths($request, $query, 'won_date');
    }

    /**
     * Get the ranges available for the chart.
     */
    public function ranges(): array
    {
        return [
            3 => __('core::dates.periods.last_3_months'),
            6 => __('core::dates.periods.last_6_months'),
            12 => __('core::dates.periods.last_12_months'),
        ];
    }

    /**
     * The card name
     */
    public function name(): string
    {
        return __('deals::deal.cards.won_by_month');
    }

    /**
     * Check whether the current user can perform user filter.
     */
    public function authorizedToFilterByUser(): bool
    {
        return request()->user()->canAny(['view all deals', 'view team deals']);
    }
}
