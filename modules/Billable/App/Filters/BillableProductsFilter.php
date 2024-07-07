<?php
 

namespace Modules\Billable\App\Filters;

use Modules\Core\App\Filters\HasFilter;
use Modules\Core\App\Filters\Number;
use Modules\Core\App\Filters\Operand;
use Modules\Core\App\Filters\Text;
use Modules\Core\App\QueryBuilder\Parser;

class BillableProductsFilter extends HasFilter
{
    /**
     * Initialize BillableProductsFilter class
     *
     * @param  string  $singularLabel
     */
    public function __construct()
    {
        parent::__construct('products', __('billable::product.product'));

        $this->setOperands([
            Operand::from(Number::make('total_count', __('billable::product.total_products'))->countableRelation('products')),
            Operand::from(Text::make('name', __('billable::product.name'))->withoutNullOperators()),
            Operand::from(Number::make('qty', __('billable::product.quantity'))),
            Operand::from(Text::make('unit', __('billable::product.unit'))),
            Operand::from(Text::make('sku', __('billable::product.sku'))->query(function ($builder, $value, $condition, $sqlOperator, $rule, Parser $parser) {
                return $builder->whereHas(
                    'originalProduct',
                    function ($query) use ($value, $parser, $rule, $condition, $sqlOperator) {
                        return $parser->convertToQuery($query, $rule, $value, $sqlOperator['operator'], $condition);
                    }
                );
            })),
        ]);
    }
}
