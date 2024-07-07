<?php
 

namespace Modules\Deals\App\Cards;

use Illuminate\Http\Request;
use Modules\Deals\App\Criteria\ViewAuthorizedDealsCriteria;
use Modules\Deals\App\Models\Deal;

class DealsWonInStage extends DealPresentationCard
{
    /**
     * Axis Y Offset
     */
    public int $axisYOffset = 150;

    /**
     * Indicates whether the cart is horizontal
     */
    public bool $horizontal = true;

    /**
     * The default renge/period selected
     *
     * @var string
     */
    public string|int|null $defaultRange = 3;

    /**
     * Calculate the deals lost in stage
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        $query = Deal::won()
            ->criteria(ViewAuthorizedDealsCriteria::class)
            ->ofPipeline($this->getPipelineId($request));

        $result = $this->byMonths('won_date')->count($request, $query, 'stage_id');

        $result->value($this->sortResultByStagesDisplayOrder($result->value));

        return $this->withStageLabels($result);
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
        return __('deals::deal.cards.won_in_stage');
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'helpText' => __('deals::deal.cards.won_in_stage_info'),
        ]);
    }
}
