<?php
 

namespace Modules\Deals\App\Cards;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Core\App\Card\TableCard;
use Modules\Core\App\Support\Carbon;
use Modules\Users\App\Models\User;

class AssignedDealsBySaleAgent extends TableCard
{
    /**
     * Provide the table items.
     *
     * @return \Illuminate\Support\Collection
     */
    public function items(Request $request): iterable
    {
        $range = $this->getCurrentRange($request);
        $startingDate = $this->getStartingDate($range, static::BY_MONTHS);
        $endingDate = Carbon::asAppTimezone();

        /** @var \Modules\Users\App\Models\User */
        $currentUser = Auth::user();

        return User::select(['id', 'name'])
            ->withCount(['deals' => function (Builder $query) use ($startingDate, $endingDate) {
                $query->whereBetween(
                    $query->getModel()->getCreatedAtColumn(),
                    [$startingDate, $endingDate]
                );
            }])
            ->withSum([
                'deals as forecast_amount' => function (Builder $query) use ($startingDate, $endingDate) {
                    $query->whereBetween(
                        $query->getModel()->getCreatedAtColumn(),
                        [$startingDate, $endingDate]
                    );
                }], 'amount')
            ->withSum([
                'deals as closed_amount' => function (Builder $query) use ($startingDate, $endingDate) {
                    $query->won()
                        ->whereBetween(
                            $query->getModel()->getCreatedAtColumn(),
                            [$startingDate, $endingDate]
                        );
                }], 'amount')
            ->when(
                $currentUser->cant('view all deals'), fn (Builder $query) => $query->ofManager($currentUser)
            )
            ->get()
            ->map(fn (User $user) => [
                'name' => $user->name,
                'deals_count' => $user->deals_count,
                'forecast_amount' => to_money($user->forecast_amount ?: 0)->format(),
                'closed_amount' => to_money($user->closed_amount ?: 0)->format(),
            ])
            ->sortByDesc('deals_count')
            ->values();
    }

    /**
     * Provide the table fields
     */
    public function fields(): array
    {
        return [
            ['key' => 'name', 'label' => __('users::user.sales_agent')],
            ['key' => 'deals_count', 'label' => __('deals::deal.total_assigned')],
            ['key' => 'forecast_amount', 'label' => __('deals::deal.forecast_amount')],
            ['key' => 'closed_amount', 'label' => __('deals::deal.closed_amount')],
        ];
    }

    /**
     * Card title
     */
    public function name(): string
    {
        return __('deals::deal.cards.assigned_by_sale_agent');
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
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'helpText' => __('deals::deal.cards.assigned_by_sale_agent_info'),
        ]);
    }
}
