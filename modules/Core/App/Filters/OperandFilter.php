<?php
 

namespace Modules\Core\App\Filters;

/**
 *   USAGE:
 *   OperandFilter::make('revenue', 'Revenue')->setOperands([
 *       Operand::make('total_revenue', 'Total Revenue')->filter(NumericFilter::class),
 *       Operand::make('annual_revenue', 'Annual Revenue')->filter(NumericFilter::class),
 *   [),
 */
class OperandFilter extends Filter
{
    /**
     * Filter current operand.
     */
    protected ?string $operand = null;

    /**
     * Filter current operands.
     *
     * @var null|array|callable
     */
    protected $operands = null;

    /**
     * Set the filter selected operand.
     */
    public function setOperand(string $operand): static
    {
        $this->operand = $operand;

        return $this;
    }

    /**
     * Get the filter selected operand.
     */
    public function getOperand(): ?string
    {
        return $this->operand;
    }

    /**
     * Set the filter available operands.
     */
    public function setOperands(array|callable|null $operands): static
    {
        $this->operands = $operands;

        return $this;
    }

    /**
     * Get the filter available operands.
     */
    public function getOperands(): ?array
    {
        if (is_callable($this->operands)) {
            return call_user_func($this->operands);
        }

        return $this->operands;
    }

    /**
     * Check whether the filter has operands.
     */
    public function hasOperands(): bool
    {
        $operands = $this->getOperands();

        return is_array($operands) && count($operands) > 0;
    }

    /**
     * Find operand filter by given value.
     */
    public function findOperand($value): ?Operand
    {
        return collect($this->getOperands())->first(fn (Operand $operand) => $operand->value == $value);
    }

    /**
     * Hide the filter operands.
     *
     * Useful when only 1 operand is used, which is by default pre-selected.
     */
    public function hideOperands(bool $bool = true): static
    {
        $this->withMeta([__FUNCTION__ => $bool]);

        return $this;
    }

    /**
     * Defines a filter type.
     */
    public function type(): string
    {
        return 'nullable';
    }
}
