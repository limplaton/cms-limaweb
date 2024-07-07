<?php
 

namespace Modules\Deals\App\Filters;

use Modules\Core\App\Filters\Date;
use Modules\Core\App\Filters\HasFilter;
use Modules\Core\App\Filters\Number;
use Modules\Core\App\Filters\Numeric;
use Modules\Core\App\Filters\Operand;

class ResourceDealsFilter extends HasFilter
{
    /**
     * Initialize ResourceDealsFilter class
     *
     * @param  string  $singularLabel
     */
    public function __construct($singularLabel)
    {
        parent::__construct('deals', __('deals::deal.deals'));

        $this->setOperands([
            Operand::from(Numeric::make('amount', __('deals::deal.deal_amount'))),
            Operand::from(Date::make('expected_close_date', __('deals::deal.deal_expected_close_date'))),
            Operand::from(
                Number::make('open_count', __('deals::deal.count.open', ['resource' => $singularLabel]))->countableRelation('openDeals')
            ),
            Operand::from(
                Number::make('won_count', __('deals::deal.count.won', ['resource' => $singularLabel]))->countableRelation('wonDeals')
            ),
            Operand::from(
                Number::make('lost_count', __('deals::deal.count.lost', ['resource' => $singularLabel]))->countableRelation('lostDeals')
            ),
            Operand::from(
                Number::make('closed_count', __('deals::deal.count.closed', ['resource' => $singularLabel]))->countableRelation('closedDeals')
            ),
        ]);
    }
}
