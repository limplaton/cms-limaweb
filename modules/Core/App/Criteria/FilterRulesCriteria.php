<?php
 

namespace Modules\Core\App\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Core\App\Contracts\Criteria\QueryCriteria;
use Modules\Core\App\Models\Filter;
use Modules\Core\App\QueryBuilder\JoinRelationParser;
use Modules\Core\App\QueryBuilder\Parser;
use Modules\Users\App\Filters\UserFilter;

class FilterRulesCriteria implements QueryCriteria
{
    /**
     * The workable query object
     *
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $query;

    /**
     * @var string|null
     */
    protected $identifier;

    /**
     * @var string|null
     */
    protected $view;

    /**
     * @param  array|object|null  $rules  The request rules
     * @param  \Illuminate\Support\Collection  $filters  All resource available filters
     * @param \Illuminate\Http\Request
     */
    public function __construct(protected $rules, protected $filters, protected Request $request)
    {
    }

    /**
     * Apply the criteria for the given query.
     */
    public function apply(Builder $query): void
    {
        $this->prepareRules();

        if (! Parser::validate($this->rules)) {
            return;
        }

        $this->query = $query;

        $this->setSpecialValueMe($this->rules->children);

        $this->query->where(function ($instance) use ($query) {
            $this->createParser()->parse(
                $this->rules,
                $instance
            );

            // On the parent model query remove any global scopes
            // that are removed from the where for the current query instance
            // for example, soft deleted when calling onlyTrashed
            $query->withoutGlobalScopes($instance->removedScopes());
        });
    }

    /**
     * Set the filters identifier
     *
     * @param  string  $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Set the filters view name where the rules are applied
     *
     * @param  string  $view
     */
    public function setView($view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Create the filters parser
     *
     * @return Modules\Core\App\QueryBuilder\Parser
     */
    public function createParser()
    {
        return $this->hasRulesRelations($this->rules->children)
                ? new JoinRelationParser($this->filters, $this->prepareParserJoinFields($this->rules->children))
                : new Parser($this->filters);
    }

    /**
     * Check whether the rules from the requests includes a relation
     *
     * @param  object  $rules
     * @return bool
     */
    protected function hasRulesRelations($rules)
    {
        $retVal = false;

        foreach ($rules as $rule) {
            if (Parser::isNested($rule)) {
                $retVal = $this->hasRulesRelations($rule->query->children);
            } else {
                // isset($rule->query->rule) - maybe the group is empty, will throw an error if not checked
                if (isset($rule->query->rule) && $this->isFieldRelation($rule->query->rule)) {
                    return true;
                }
            }
        }

        return $retVal;
    }

    /**
     * Check if field is relation e.q. contact.first_name
     *
     * @param  string  $name  QueryBuilder Rule ID
     * @return bool
     */
    protected function isFieldRelation($name)
    {
        if (str_contains($name, '.')) {
            // Perhaps is e.q. companies.name with table prefix
            $ruleArray = array_reverse(explode('.', $name));
            $relationName = end($ruleArray);

            // Not defined, not relation
            return $this->query->getModel()->isRelation($relationName);
        }

        return false;
    }

    /**
     * Get relation data to be used in query has
     *
     *    return $query->whereHas($relation, function ($query) use ($field, $operator, $value, $condition) {
     *       $query->where($field, $operator, $value, $condition);
     *    });
     *
     * @param  string  $name  QueryBuilder Rule ID
     * @return array
     */
    protected function getRelationFieldDataForQuery($name)
    {
        if (! $this->isFieldRelation($name)) {
            return false;
        }

        $explode = explode('.', $name);
        $field = array_pop($explode);
        $relation = implode('.', $explode);

        return ['field' => $field, 'relation' => $relation];
    }

    /**
     * Prepare the rules for the parser.
     */
    protected function prepareRules()
    {
        $rules = $this->rules;

        if (! $this->hasRules()) {
            if ($this->request->has('filter_id')) {
                $rules = $this->findFilter($this->request->integer('filter_id'))?->rules;
            } elseif ($this->request->boolean('with_default_filter')) {
                $rules = $this->getDefaultFilter()?->rules;
            }
        }

        return $this->rules = is_array($rules) ? Arr::toObject($rules) : $rules;
    }

    /**
     * Check whether there are rules for the current request.
     */
    protected function hasRules(): bool
    {
        return with($this->rules, function ($rules) {
            return ! (! $rules || count(is_array($rules) ? $rules['children'] ?? [] : $rules->children ?? []) === 0);
        });
    }

    /**
     * Find filter for the current user by given ID.
     */
    protected function findFilter(int $filterId)
    {
        return Filter::visibleFor(Auth::id())
            ->when($this->identifier, function ($query) {
                $query->ofIdentifier($this->identifier);
            })->find($filterId);
    }

    /**
     * Set the special value me to the actual logged in user id
     *
     * This is only applied for User filter
     *
     * @return void
     */
    protected function setSpecialValueMe($rules)
    {
        foreach ($rules as $rule) {
            if (Parser::isNested($rule)) {
                $this->setSpecialValueMe($rule->query->children);
            } elseif ($this->isUserSpecialRule($rule)) {
                $rule->query->value = Auth::id();
            }
        }
    }

    /**
     * Check whether the given rule is the special user file
     *
     * @param  \stdClass  $rule
     * @return bool
     */
    protected function isUserSpecialRule($rule)
    {
        return isset($rule->query->rule) &&
            ! is_null($this->filters->first(function ($filter) use ($rule) {
                return $filter->field() === $rule->query->rule && $filter instanceof UserFilter;
            })) &&
            $rule->query->value === 'me';
    }

    /**
     * Get the default filter for the current request and view.
     */
    protected function getDefaultFilter(): ?Filter
    {
        return Filter::hasDefaultFor(
            $this->identifier,
            $this->view ?? $this->identifier,
            Auth::id()
        )->first();
    }

    /**
     * Prepares the join fields for the parser
     *
     * @param  \stdClass  $rules
     * @return array
     */
    protected function prepareParserJoinFields($rules)
    {
        $retVal = [];

        foreach ($rules as $rule) {
            if (Parser::isNested($rule)) {
                $retVal = $this->prepareParserJoinFields($rule->query->children);
            } else {
                if ($relationJoinData = $this->getRelationFieldDataForQuery($rule->query->rule)) {
                    $masterModel = $this->query->getModel();
                    $relationModel = $masterModel->{$relationJoinData['relation']}()->getModel();

                    $retVal[$rule->query->rule] = [];

                    $retVal[$rule->query->rule]['from_table'] = $masterModel->getConnection()->getTablePrefix().$masterModel->getTable();
                    $retVal[$rule->query->rule]['from_col'] = Str::singular($relationJoinData['relation']).'_'.$relationModel->getKeyName();
                    $retVal[$rule->query->rule]['to_table'] = $masterModel->getConnection()->getTablePrefix().$relationModel->getTable();
                    $retVal[$rule->query->rule]['to_col'] = $relationModel->getKeyName();
                    $retVal[$rule->query->rule]['to_value_column'] = $relationJoinData['field'];
                }
            }
        }

        return $retVal;
    }
}
