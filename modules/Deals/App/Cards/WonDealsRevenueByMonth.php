<?php
 

namespace Modules\Deals\App\Cards;

use Illuminate\Http\Request;
use Modules\Core\App\Charts\Progression;
use Modules\Deals\App\Models\Deal;

class WonDealsRevenueByMonth extends Progression
{
    /**
     * Indicates whether the chart values are amount
     */
    protected bool $amountValue = true;

    /**
     * Calculate the won deals revenue by month
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        return $this->sumByMonths($request, Deal::won(), 'amount', 'won_date');
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
        return __('deals::deal.cards.won_by_revenue_by_month');
    }
}
