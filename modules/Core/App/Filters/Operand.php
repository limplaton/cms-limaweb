<?php
 

namespace Modules\Core\App\Filters;

use Exception;
use JsonSerializable;
use Modules\Core\App\Support\Makeable;

class Operand implements JsonSerializable
{
    use Makeable;

    /**
     * The rule the oprand is related to.
     */
    public ?Filter $rule = null;

    /**
     * The key value key should be taken from.
     */
    public string $valueKey = 'value';

    /**
     * The key label key should be taken from.
     */
    public string $labelKey = 'label';

    /**
     * Initialize new Operand instance.
     */
    public function __construct(public mixed $value, public string $label)
    {
    }

    /**
     * Create an operand from the given filter.
     */
    public static function from(Filter $filter)
    {
        return (new static($filter->field, $filter->label))->filter($filter);
    }

    /**
     * Set the operand filter.
     */
    public function filter(string|Filter $rule): static
    {
        if (is_string($rule)) {
            $rule = $rule::make($this->value);
        }

        if ($rule instanceof OperandFilter || $rule instanceof HasFilter) {
            throw new Exception(sprintf('Cannot use %s filter in operands.', [$rule::class]));
        }

        $this->rule = $rule;

        return $this;
    }

    /**
     * Set custom key for value.
     */
    public function valueKey(string $key): static
    {
        $this->valueKey = $key;

        return $this;
    }

    /**
     * Set custom label key.
     */
    public function labelKey(string $key): static
    {
        $this->labelKey = $key;

        return $this;
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label,
            'valueKey' => $this->valueKey,
            'labelKey' => $this->labelKey,
            'rule' => $this->rule,
        ];
    }
}
