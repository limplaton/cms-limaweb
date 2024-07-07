<?php
 

namespace Modules\Deals\App\Cards;

use Illuminate\Http\Request;
use Modules\Core\App\Card\TableCard;
use Modules\Core\App\Support\Carbon;
use Modules\Deals\App\Criteria\ViewAuthorizedDealsCriteria;
use Modules\Deals\App\Models\Deal;

class RecentlyCreatedDeals extends TableCard
{
    /**
     * Limit the number of records shown in the table
     *
     * @var int
     */
    protected $limit = 20;

    /**
     * Created in the last 30 days
     *
     * @var int
     */
    protected $days = 30;

    /**
     * Provide the table items.
     *
     * @return \Illuminate\Support\Collection
     */
    public function items(Request $request): iterable
    {
        return Deal::select(['id', 'name', 'created_at', 'stage_id'])
            ->criteria(ViewAuthorizedDealsCriteria::class)
            ->with(['stage' => fn ($query) => $query->select(['id', 'name'])])
            ->where('created_at', '>', Carbon::asCurrentTimezone()->subDays($this->days)->inAppTimezone())
            ->latest()
            ->limit($this->limit)
            ->get()
            ->map(fn (Deal $deal) => [
                'id' => $deal->id,
                'name' => $deal->name,
                'stage' => $deal->stage,
                'created_at' => $deal->created_at,
                'path' => $deal->path(),
            ]);
    }

    /**
     * Provide the table fields
     */
    public function fields(): array
    {
        return [
            ['key' => 'name', 'label' => __('deals::fields.deals.name')],
            ['key' => 'stage.name', 'label' => __('deals::fields.deals.stage.name')],
            ['key' => 'created_at', 'label' => __('core::app.created_at')],
        ];
    }

    /**
     * Card title
     */
    public function name(): string
    {
        return __('deals::deal.cards.recently_created');
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'helpText' => __('deals::deal.cards.recently_created_info', ['total' => $this->limit, 'days' => $this->days]),
        ]);
    }
}
