<?php
 

namespace Modules\Core\App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\App\QueryBuilder\Parser;

class HasFilter extends OperandFilter
{
    /**
     * Apply the filter when custom query callback is provided
     *
     * @param  mixed  $value
     * @param  string  $condition
     * @param  array  $sqlOperator
     * @param  \stdClass  $rule
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Builder $builder, $value, $condition, $sqlOperator, $rule, Parser $parser)
    {
        if ($parser->ruleCountsRelation($rule->operand->rule)) {
            return $parser->makeQueryWhenCountableRelation(
                $builder,
                $rule->operand->rule,
                $rule,
                $sqlOperator['operator'],
                $value,
                $condition,
                function (Builder $builder) {
                    $this->applyViewAuthorizedCriteriaIfNeeded($builder);
                }
            );
        }

        return $builder->has($this->field(), '>=', 1, $condition, function ($builder) use ($rule, $parser) {
            $this->applyViewAuthorizedCriteriaIfNeeded($builder);

            // Use AND for the subquery of the relation rules
            return $parser->makeQuery($builder, $rule, 'AND');
        });
    }

    /**
     * Apply view authorized criteria to the builder if the builder model is associated with resources
     */
    protected function applyViewAuthorizedCriteriaIfNeeded(Builder $query): Builder
    {
        if (
            method_exists($query->getModel(), 'resource') &&
            $criteria = $query->getModel()->resource()->viewAuthorizedRecordsCriteria()
        ) {
            (new $criteria)->apply($query);
        }

        return $query;
    }

    /**
     * Check whether the filter has custom callback
     */
    public function hasCustomQuery(): bool
    {
        return true;
    }
}
