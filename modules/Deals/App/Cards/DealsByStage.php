<?php
 

namespace Modules\Deals\App\Cards;

use Illuminate\Http\Request;
use Modules\Deals\App\Criteria\ViewAuthorizedDealsCriteria;
use Modules\Deals\App\Models\Deal;

class DealsByStage extends DealPresentationCard
{
    /**
     * The default renge/period selected
     *
     * @var int
     */
    public string|int|null $defaultRange = 30;

    /**
     * Calculate the deals by stage
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        $query = Deal::open()
            ->criteria(ViewAuthorizedDealsCriteria::class)
            ->ofPipeline($this->getPipelineId($request));

        $result = $this->byDays('created_at')->count($request, $query, 'stage_id');

        $result->value($this->sortResultByStagesDisplayOrder($result->value));

        return $this->withStageLabels($result);
    }

    /**
     * The card name
     */
    public function name(): string
    {
        return __('deals::deal.cards.by_stage');
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
}
