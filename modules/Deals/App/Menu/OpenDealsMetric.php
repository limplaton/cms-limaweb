<?php
 

namespace Modules\Deals\App\Menu;

use Modules\Core\App\Menu\Metric;
use Modules\Core\App\Models\Filter;
use Modules\Deals\App\Criteria\ViewAuthorizedDealsCriteria;
use Modules\Deals\App\Models\Deal;

class OpenDealsMetric extends Metric
{
    /**
     * Get the metric name
     */
    public function name(): string
    {
        return __('deals::deal.metrics.open');
    }

    /**
     * Get the metric count
     */
    public function count(): int
    {
        return Deal::criteria(ViewAuthorizedDealsCriteria::class)->open()->count();
    }

    /**
     * Get the background color variant when the metric count is bigger then zero
     */
    public function backgroundColorVariant(): string
    {
        return 'info';
    }

    /**
     * Get the front-end route that the highly will redirect to
     */
    public function route(): array|string
    {
        $filter = Filter::findByFlag('open-deals');

        return [
            'name' => 'deal-index',
            'query' => [
                'filter_id' => $filter?->id,
            ],
        ];
    }
}
