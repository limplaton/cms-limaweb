<?php
 

namespace Modules\Contacts\App\Cards;

use Illuminate\Http\Request;
use Modules\Contacts\App\Criteria\ViewAuthorizedContactsCriteria;
use Modules\Contacts\App\Models\Contact;
use Modules\Core\App\Charts\Progression;

class ContactsByDay extends Progression
{
    /**
     * Calculates contacts created by day
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        return $this->countByDays($request, Contact::criteria(ViewAuthorizedContactsCriteria::class));
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
        return __('contacts::contact.cards.by_day');
    }
}
