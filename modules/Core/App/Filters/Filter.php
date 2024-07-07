<?php
 

namespace Modules\Core\App\Filters;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use JsonSerializable;
use Modules\Core\App\QueryBuilder\Parser;
use Modules\Core\App\QueryBuilder\ParserTrait;
use Modules\Core\App\Support\Authorizeable;
use Modules\Core\App\Support\HasHelpText;
use Modules\Core\App\Support\HasMeta;
use Modules\Core\App\Support\Makeable;

class Filter implements Arrayable, JsonSerializable
{
    use Authorizeable,
        HasHelpText,
        HasMeta,
        Makeable,
        ParserTrait;

    /**
     * Define builder rule custom component
     */
    public $component = null;

    /**
     * Filter field/rule
     *
     * @var string
     */
    public $field;

    /**
     * Filter label
     *
     * @var string|null
     */
    public $label;

    /**
     * Filter operators
     */
    public array $filterOperators = [];

    /**
     * Exclude operators
     */
    public array $excludeOperators = [];

    /**
     * @var null|callable
     */
    public $tapCallback;

    /**
     * Indicates whether the filter is static
     */
    public bool $static = false;

    /**
     * @var null|callable
     */
    public $callback;

    /**
     * Filter current operator
     */
    protected ?string $operator = null;

    /**
     * Filter current value
     *
     * @var array|string|null
     */
    protected $value;

    /**
     * Custom display as text
     *
     * @var string|array|null
     */
    protected $displayAs = null;

    /**
     * @param  string  $field
     * @param  string|null  $label
     * @param  null|array  $operators
     */
    public function __construct($field, $label = null, $operators = null)
    {
        $this->field = $field;
        $this->label = $label;

        is_array($operators) ? $this->operators($operators) : $this->determineOperators();
    }

    /**
     * Filter type from available filter types developed for front end
     */
    public function type(): ?string
    {
        return null;
    }

    /**
     * Get the filter component
     */
    public function component(): string
    {
        return $this->component ? $this->component : $this->type().'-rule';
    }

    /**
     * Set custom operators
     */
    public function operators(array $operators): static
    {
        $this->filterOperators = $operators;

        return $this;
    }

    /**
     * Exclude the empty operators
     */
    public function withoutEmptyOperators(): static
    {
        $this->withoutOperators(['is_empty', 'is_not_empty']);

        return $this;
    }

    /**
     * Exclude the null operators
     */
    public function withoutNullOperators(): static
    {
        $this->withoutOperators(['is_null', 'is_not_null']);

        return $this;
    }

    /**
     * Exclude operators
     */
    public function withoutOperators(string|array $operator): static
    {
        $this->excludeOperators = is_array($operator) ? $operator : func_get_args();

        return $this;
    }

    /**
     * Get the filter field
     *
     * @return string
     */
    public function field()
    {
        return $this->field;
    }

    /**
     * Get the filter label
     *
     * @return string
     */
    public function label()
    {
        return $this->label;
    }

    /**
     * Add custom query handler instead of using the query builder parser
     */
    public function query(callable $callback): static
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * Add query tap callback
     */
    public function tapQuery(callable $callback): static
    {
        $this->tapCallback = $callback;

        return $this;
    }

    /**
     * Apply the filter when custom query callback is provided
     *
     * @param  mixed  $value
     * @param  string  $condition
     * @param  array  $sqlOperator
     * @param  stdClass  $rule
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Builder $builder, $value, $condition, $sqlOperator, $rule, Parser $parser)
    {
        return call_user_func(
            $this->callback,
            $builder,
            $value,
            $condition,
            $sqlOperator,
            $rule,
            $parser
        );
    }

    /**
     * Mark the filter as static
     */
    public function asStatic(): static
    {
        $this->static = true;
        $this->component = 'static-rule';

        return $this;
    }

    /**
     * Add display
     *
     * @param  mixed  $value
     */
    public function displayAs($value): static
    {
        $this->displayAs = $value;

        return $this;
    }

    /**
     * Determine whether the filter is static
     */
    public function isStatic(): bool
    {
        return $this->static === true;
    }

    /**
     * Check whether the filter is optionable
     */
    public function isOptionable(): bool
    {
        if ($this->isMultiOptionable()) {
            return true;
        }

        return $this instanceof Optionable;
    }

    /**
     * Check whether the filter is multi optionable
     */
    public function isMultiOptionable(): bool
    {
        return $this instanceof MultiSelect || $this instanceof Checkbox;
    }

    /**
     * Check whether the filter has custom callback
     */
    public function hasCustomQuery(): bool
    {
        return ! is_null($this->callback);
    }

    /**
     * Set the filter current value
     *
     * @param  string|array  $value
     */
    public function setValue($value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get the filter active value
     *
     * @return string|array|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the filter current operator
     */
    public function setOperator(?string $operator): static
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * Get the filter current operator
     *
     * @return string|null
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * Create rule able array from the filter
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getBuilderData();
    }

    /**
     * Get the fillter operators
     */
    protected function getOperators(): array
    {
        return array_values(
            array_diff(
                array_unique($this->filterOperators),
                $this->excludeOperators
            )
        );
    }

    /**
     * Get operators options
     */
    protected function operatorsOptions(): array
    {
        $options = [];

        foreach ($this->getOperators() as $operator) {
            $method = Str::studly(str_replace('.', '_', $operator)).'OperatorOptions';

            if (method_exists($this, $method)) {
                $options[$operator] = $this->{$method}() ?: [];
            }
        }

        return $options;
    }

    /**
     * Auto determines the operators on initialize based on ParserTrait
     */
    private function determineOperators(): void
    {
        foreach ($this->operators as $operator => $data) {
            if (in_array($this->type(), $data['apply_to'])) {
                $this->filterOperators[] = $operator;
            }
        }
    }

    /**
     * Get the filter builder data
     */
    public function getBuilderData(): array
    {
        return [
            'type' => 'rule',
            'query' => array_filter([
                'type' => $this->type(),
                'rule' => $this->field(),
                'operator' => $this->getOperator(),
                'operand' => $this instanceof OperandFilter ? $this->operand : null,
                'value' => $this->getValue(),
            ]),
        ];
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return array_merge([
            'id' => $this->field(),
            'label' => $this->label(),
            'type' => $this->type(),
            'operators' => $this->getOperators(),
            'operatorsOptions' => $this->operatorsOptions(),
            'component' => $this->component(),
            'isStatic' => $this->isStatic(),
            'operands' => $this instanceof OperandFilter ? $this->getOperands() : [],
            'has_authorization' => $this->hasAuthorization(),
            'helpText' => $this->helpText,
            'displayAs' => $this->displayAs,
        ], $this->meta());
    }
}
