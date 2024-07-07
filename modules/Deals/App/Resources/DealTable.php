<?php
 

namespace Modules\Deals\App\Resources;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\App\Table\Table;
use Modules\Deals\App\Criteria\ViewAuthorizedDealsCriteria;
use Modules\Deals\App\Models\Deal;
use Modules\Deals\App\Models\Stage;

class DealTable extends Table
{
    /**
     * Additional database columns to select for the table query.
     */
    protected array $select = [
        'user_id', // user_id is for the policy checks
        'expected_close_date', // falls_behind_expected_close_date check
        'status', // falls_behind_expected_close_date check
    ];

    /**
     * Attributes to be appended with the response.
     */
    protected array $appends = [
        'falls_behind_expected_close_date', // row class
    ];

    /**
     * Whether the table columns can be customized.
     */
    public bool $customizeable = true;

    /**
     * Whether the table has actions column.
     */
    public bool $withActionsColumn = true;

    /**
     * Tap the response
     */
    protected function tapResponse(LengthAwarePaginator $response): void
    {
        $query = Deal::criteria([
            $this->newRequestCriteria(),
            $this->newFilterRulesCriteria(),
            ViewAuthorizedDealsCriteria::class,
        ]);

        $summary = Stage::summary($query);

        $this->meta = ['summary' => [
            'count' => $summary->sum('count'),
            'value' => $summary->sum('value'),
            'weighted_value' => $summary->sum('weighted_value'),
        ]];
    }

    /**
     * Boot table
     */
    public function boot(): void
    {
        $this->orderBy('created_at', 'desc')->rowBorderVariant(function (array $row) {
            return $row['falls_behind_expected_close_date'] ? 'warning' : null;
        });
    }
}
