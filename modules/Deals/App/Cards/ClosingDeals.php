<?php
 

namespace Modules\Deals\App\Cards;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\Request;
use Modules\Core\App\Card\TableAsyncCard;
use Modules\Core\App\Support\ProvidesBetweenArgumentsViaString;
use Modules\Deals\App\Criteria\ViewAuthorizedDealsCriteria;
use Modules\Deals\App\Models\Deal;
use Modules\Users\App\Criteria\QueriesByUserCriteria;

class ClosingDeals extends TableAsyncCard
{
    use ProvidesBetweenArgumentsViaString;

    /**
     * The default renge/period selected
     *
     * @var string
     */
    public string|int|null $defaultRange = 'this_month';

    /**
     * Default sort field
     */
    protected Expression|string|null $sortBy = 'expected_close_date';

    /**
     * Provide the query that will be used to retrieve the items.
     */
    public function query(Request $request): Builder
    {
        $query = Deal::select(['id', 'name', 'expected_close_date'])
            ->open()
            ->criteria(ViewAuthorizedDealsCriteria::class);

        if ($userId = $this->getUserId($request)) {
            $query->criteria(new QueriesByUserCriteria($userId));
        }

        $period = $this->getBetweenArguments($request->range ?? $this->defaultRange);

        return $query->whereNotNull('expected_close_date')
            ->whereBetween('expected_close_date', $period);
    }

    /**
     * Provide the table fields
     */
    public function fields(): array
    {
        return [
            ['key' => 'name', 'label' => __('deals::fields.deals.name'), 'sortable' => true],
            ['key' => 'expected_close_date', 'label' => __('deals::fields.deals.expected_close_date'), 'sortable' => true],
        ];
    }

    /**
     * Get the searchable columns.
     */
    protected function getSearchableColumns(): array
    {
        return ['name' => 'like', 'pipeline.name', 'stage.name'];
    }

    /**
     * Get the ranges available for the chart.
     */
    public function ranges(): array
    {
        return [
            'this_week' => __('core::dates.this_week'),
            'this_month' => __('core::dates.this_month'),
            'next_week' => __('core::dates.next_week'),
            'next_month' => __('core::dates.next_month'),
        ];
    }

    /**
     * The card name
     */
    public function name(): string
    {
        return __('deals::deal.cards.closing');
    }

    /**
     * Check whether the current user can perform user filter.
     */
    public function authorizedToFilterByUser(): bool
    {
        return request()->user()->canAny(['view all deals', 'view team deals']);
    }
}
