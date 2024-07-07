<?php
 

namespace Modules\Core\App\Charts;

use Illuminate\Http\Request;
use Modules\Core\App\Card\Card;

abstract class Chart extends Card
{
    /**
     * Indicates whether the chart values are amount
     */
    protected bool $amountValue = false;

    /**
     * Chart color/variant class
     */
    protected ?string $color = null;

    /**
     * The method to perform the line chart calculations
     *
     * @return mixed
     */
    abstract public function calculate(Request $request);

    /**
     * Get the card value.
     */
    public function value(Request $request): mixed
    {
        return $this->calculate($request);
    }

    /**
     * The chart available labels
     */
    public function labels($result): array
    {
        return [];
    }

    /**
     * Set chart color
     */
    public function color(string $color): static
    {
        $this->color = 'chart-'.$color;

        return $this;
    }

    /**
     * Prepate the data for the front-end
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'amount_value' => $this->amountValue,
            'color' => $this->color,
        ]);
    }
}
