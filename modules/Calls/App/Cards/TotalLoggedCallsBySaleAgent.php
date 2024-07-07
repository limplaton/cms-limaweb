<?php
 

namespace Modules\Calls\App\Cards;

use Illuminate\Http\Request;
use Modules\Calls\App\Models\Call;
use Modules\Core\App\Charts\Presentation;
use Modules\Users\App\Models\User;

class TotalLoggedCallsBySaleAgent extends Presentation
{
    /**
     * Axis Y Offset
     */
    public int $axisYOffset = 150;

    /**
     * Indicates whether the cart is horizontal
     */
    public bool $horizontal = true;

    /**
     * The default renge/period selected
     *
     * @var int
     */
    public string|int|null $defaultRange = 30;

    /**
     * Calculates logged calls by sales agent
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        return $this->byDays('date')->count($request, Call::class, 'user_id')
            ->label(function ($value) {
                return $this->users()->find($value)->name;
            });
    }

    /**
     * Get the ranges available for the chart.
     */
    public function ranges(): array
    {
        return [
            7 => __('core::dates.periods.7_days'),
            15 => __('core::dates.periods.15_days'),
            30 => __('core::dates.periods.30_days'),
            60 => __('core::dates.periods.60_days'),
            90 => __('core::dates.periods.90_days'),
            365 => __('core::dates.periods.365_days'),
        ];
    }

    /**
     * Get the all available users
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function users()
    {
        return once(function () {
            return User::select(['id', 'name'])->get();
        });
    }

    /**
     * The card name
     */
    public function name(): string
    {
        return __('calls::call.cards.by_sale_agent');
    }
}
