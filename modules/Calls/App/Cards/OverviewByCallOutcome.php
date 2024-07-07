<?php
 

namespace Modules\Calls\App\Cards;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Calls\App\Models\Call;
use Modules\Calls\App\Models\CallOutcome;
use Modules\Core\App\Charts\Presentation;
use Modules\Core\App\Criteria\RelatedCriteria;
use Modules\Users\App\Criteria\QueriesByUserCriteria;

class OverviewByCallOutcome extends Presentation
{
    /**
     * The default renge/period selected.
     *
     * @var int
     */
    public string|int|null $defaultRange = 30;

    /**
     * Outcomes cache.
     */
    protected ?Collection $outcomes = null;

    /**
     * Calculated overview by call outcome.
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        $query = (new Call)->newQuery();

        if ($userId = $this->getUserId($request)) {
            $query->criteria(new QueriesByUserCriteria($userId));
        } else {
            $query->criteria(RelatedCriteria::class);
        }

        return $this->byDays('date')
            ->count($request, $query, 'call_outcome_id')
            ->label(function ($value) {
                return $this->outcomes()->find($value)->name;
            })->colors($this->outcomes()->mapWithKeys(function (CallOutcome $outcome) {
                return [$outcome->name => $outcome->swatch_color];
            })->all());
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
     * Get all available outcomes.
     */
    public function outcomes(): Collection
    {
        if (! $this->outcomes) {
            $this->outcomes = CallOutcome::select(
                ['id', 'name', 'swatch_color']
            )->get();
        }

        return $this->outcomes;
    }

    /**
     * The card name.
     */
    public function name(): string
    {
        return __('calls::call.cards.outcome_overview');
    }

    /**
     * Check whether the current user can perform user filter.
     */
    public function authorizedToFilterByUser(): bool
    {
        return request()->user()->isSuperAdmin();
    }
}
