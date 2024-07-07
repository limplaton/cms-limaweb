<?php
 

namespace Modules\Core\App\QueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use stdClass;

class JoinRelationParser extends Parser
{
    /**
     * @var null|array an associative array of the join fields keyed by fields name, with the following keys
     */
    protected $joinFields;

    /**
     * @param  array  $joinFields  an associative array of the join fields keyed by fields name, with the following keys
     *                             - from_table       The name of the master table
     *                             - from_col         The column of the master table to use in the join
     *                             - to_table         The name of the join table
     *                             - to_col           The column of the join table to use
     *                             - to_value_column  The column of the join table containing the value to use as a
     *                             where clause
     *                             - to_clause*       An additional clause to add to the join condition, compatible
     *                             with $query->where($clause)
     *                             - not_exists*      Only return rows which do not exist in the subclause
     *
     * * optional field
     */
    public function __construct(?Collection $fields = null, ?array $joinFields = null)
    {
        parent::__construct($fields);

        $this->joinFields = $joinFields;
    }

    /**
     * Take a particular rule and make build something that the QueryBuilder would be proud of.
     *
     * Make sure that all the correct fields are in the rule object then add the expression to
     * the query that was given by the user to the QueryBuilder.
     *
     * @param  string  $queryCondition  the condition that will be used in the query
     * @return \Illuminate\Database\Eloquent\Builder
     *
     * @throws \Exception
     */
    public function makeQuery(Builder $builder, stdClass $rule, $queryCondition = 'AND')
    {
        $value = $this->getValueForQueryFromRule($rule);
        $condition = strtolower($queryCondition);

        if (is_array($this->joinFields) && array_key_exists($rule->query->rule, $this->joinFields)) {
            return $this->buildSubclauseQuery($builder, $rule, $value, $condition);
        }

        return $this->convertIncomingQBtoQuery($builder, $rule, $value, $condition);
    }

    /**
     * Build a subquery clause if there are join fields that have been specified.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \stdClass  $rule
     * @param  string|null  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildSubclauseQuery($builder, $rule, $value, $condition)
    {
        /*
         * Convert the Operator (LIKE/NOT LIKE/GREATER THAN) given to us by QueryBuilder
         * into on one that we can use inside the SQL query
         */
        $_sql_op = $this->operator_sql[$rule->query->operator];
        $operator = $_sql_op['operator'];
        $require_array = $this->operatorRequiresArray($operator);
        $subclause = $this->joinFields[$rule->query->rule];
        $subclause['operator'] = $operator;
        $subclause['value'] = $value;
        $subclause['require_array'] = $require_array;
        $not = array_key_exists('not_exists', $subclause) && $subclause['not_exists'];
        // Create a where exists clause to join to the other table, and find results matching the criteria
        $builder = $builder->whereExists(
            function (Builder $query) use ($subclause) {
                $q = $query->selectRaw(1)
                    ->from(Str::replaceFirst($query->getConnection()->getTablePrefix(), '', $subclause['to_table']))
                    ->whereRaw($subclause['to_table'].'.'.$subclause['to_col']
                        .' = '
                        .$subclause['from_table'].'.'.$subclause['from_col']);
                if (array_key_exists('to_clause', $subclause)) {
                    $q->where($subclause['to_clause']);
                }
                $this->buildSubclauseInnerQuery($subclause, $q);
            },
            $condition,
            $not
        );

        return $builder;
    }

    /**
     * The inner query for a subclause
     *
     * @see buildSubclauseQuery
     *
     * @param  array  $subclause
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildSubclauseInnerQuery($subclause, Builder $builder)
    {
        if ($subclause['require_array']) {
            return $this->buildRequireArrayQuery($subclause, $builder);
        }

        if ($subclause['operator'] == 'NULL' || $subclause['operator'] == 'NOT NULL') {
            return $this->buildSubclauseWithNull($subclause, $builder, ($subclause['operator'] == 'NOT NULL' ? true : false));
        }

        return $this->buildRequireNotArrayQuery($subclause, $builder);
    }

    /**
     * The inner query for a subclause when an array is required
     *
     * @see buildSubclauseInnerQuery
     *
     * @param  array  $subclause
     * @return \Illuminate\Database\Eloquent\Builder
     *
     * @throws \Exception when an invalid array is passed.
     */
    private function buildRequireArrayQuery($subclause, Builder $builder)
    {
        if ($subclause['operator'] == 'IN') {
            $builder->whereIn($subclause['to_value_column'], $subclause['value']);
        } elseif ($subclause['operator'] == 'NOT IN') {
            $builder->whereNotIn($subclause['to_value_column'], $subclause['value']);
        } elseif ($subclause['operator'] == 'BETWEEN') {
            if (count($subclause['value']) !== 2) {
                throw new \Exception($subclause['to_value_column'].
                    ' should be an array with only two items.');
            }
            $builder->whereBetween($subclause['to_value_column'], $subclause['value']);
        } elseif ($subclause['operator'] == 'NOT BETWEEN') {
            if (count($subclause['value']) !== 2) {
                throw new \Exception($subclause['to_value_column'].
                    ' should be an array with only two items.');
            }
            $builder->whereNotBetween($subclause['to_value_column'], $subclause['value']);
        }

        return $builder;
    }

    /**
     * The inner query for a subclause when an array is not requeired
     *
     * @see buildSubclauseInnerQuery
     *
     * @param  array  $subclause
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildRequireNotArrayQuery($subclause, Builder $builder)
    {
        return $builder->where($subclause['to_value_column'], $subclause['operator'], $subclause['value']);
    }

    /**
     * The inner query for a subclause when the operator is NULL.
     *
     * @see buildSubclauseInnerQuery
     *
     * @param  array  $subclause
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildSubclauseWithNull($subclause, Builder $builder, $isNotNull = false)
    {
        if ($isNotNull === true) {
            return $builder->whereNotNull($subclause['to_value_column']);
        }

        return $builder->whereNull($subclause['to_value_column']);
    }
}
