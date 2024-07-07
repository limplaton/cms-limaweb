<?php
 

namespace Modules\Contacts\App\Cards;

use Illuminate\Http\Request;
use Modules\Contacts\App\Criteria\ViewAuthorizedCompaniesCriteria;
use Modules\Contacts\App\Models\Company;
use Modules\Core\App\Charts\Progression;

class CompaniesByDay extends Progression
{
    /**
     * Calculates companies created by day
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        return $this->countByDays($request, Company::criteria(ViewAuthorizedCompaniesCriteria::class));
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
        return __('contacts::company.cards.by_day');
    }
}
