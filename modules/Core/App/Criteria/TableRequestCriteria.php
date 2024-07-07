<?php
 

namespace Modules\Core\App\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\App\Table\Table;

class TableRequestCriteria extends RequestCriteria
{
    /**
     * Initialize new TableRequestCriteria instance.
     */
    public function __construct(protected Table $table)
    {
        parent::__construct();
    }

    /**
     * Apply order for the given query.
     *
     * @param  mixed  $order
     * @return void
     */
    protected function applyOrder($order, Builder $query): Builder
    {
        // No order applied
        if (empty($order)) {
            return $query;
        }

        // Remove any default order
        $query->reorder();

        collect($order)
            ->map(fn (array $data) => [
                'column' => $this->table->getColumn($data['attribute']),
                'direction' => ($data['direction'] ?? '') ?: 'asc',
            ])
            ->reject(fn (array $data) => is_null($data['column']))
            ->each(function (array $data) use (&$query) {
                $query = $data['column']->orderBy($query, $data['direction']);
            });

        return $query;
    }
}
