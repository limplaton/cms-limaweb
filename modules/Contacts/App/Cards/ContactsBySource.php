<?php
 

namespace Modules\Contacts\App\Cards;

use Illuminate\Http\Request;
use Modules\Contacts\App\Criteria\ViewAuthorizedContactsCriteria;
use Modules\Contacts\App\Models\Contact;
use Modules\Contacts\App\Models\Source;
use Modules\Core\App\Charts\Presentation;

class ContactsBySource extends Presentation
{
    /**
     * The default renge/period selected
     *
     * @var int
     */
    public string|int|null $defaultRange = 30;

    /**
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $sources;

    /**
     * Calculate the contacts by source
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        $query = Contact::criteria(ViewAuthorizedContactsCriteria::class);

        return $this->byDays('created_at')->count($request, $query, 'source_id')->label(function ($value) {
            return $this->sources()->find($value)->name ?? 'N\A';
        });
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
            60 => __('core::dates.periods.60_days'),
            90 => __('core::dates.periods.90_days'),
            365 => __('core::dates.periods.365_days'),
        ];
    }

    /**
     * Get all available sources
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function sources()
    {
        return $this->sources ??= Source::get(['id', 'name']);
    }

    /**
     * The card name
     */
    public function name(): string
    {
        return __('contacts::contact.cards.by_source');
    }
}
