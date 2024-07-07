<?php
 

namespace Modules\Core\App\Charts;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\App\Support\Carbon;

abstract class Presentation extends Chart
{
    /**
     * Indicates whether the displayed values must be cast to integer
     */
    public bool $onlyInteger = true;

    /**
     * Axis X Offset
     */
    public int $axisXOffset = 30;

    /**
     * Axis Y Offset
     */
    public int $axisYOffset = 20;

    /**
     * Indicates whether the cart is horizontal
     */
    public bool $horizontal = false;

    /**
     * Rounding precision
     */
    public int $roundingPrecision = 0;

    /**
     * Rounding mode
     */
    public int $roundingMode = PHP_ROUND_HALF_UP;

    /**
     * Hold values if the result is queried by date
     */
    public ?array $queryByDate = null;

    /**
     * Count by days
     */
    public function byDays(string $dateColumn): static
    {
        $this->queryByDate = [$dateColumn, self::BY_DAYS];

        return $this;
    }

    /**
     * Count by weeks
     */
    public function byWeeks(string $dateColumn): static
    {
        $this->queryByDate = [$dateColumn, self::BY_WEEKS];

        return $this;
    }

    /**
     * Count by months
     */
    public function byMonths(string $dateColumn): static
    {
        $this->queryByDate = [$dateColumn, self::BY_MONTHS];

        return $this;
    }

    /**
     * Return a presentation result showing the segments of a count aggregate.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  string  $groupBy
     * @param  string|null  $column
     * @param  \Closure  $callback  query callback
     * @return \Modules\Core\App\Charts\ChartResult
     */
    public function count($request, $model, $groupBy, $column = null, $callback = null)
    {
        return $this->aggregate($request, $model, 'count', $column, $groupBy, $callback);
    }

    /**
     * Return a presentation result showing the segments of an average aggregate.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  string|null  $column
     * @param  string  $groupBy
     * @param  \Closure  $callback  query callback
     * @return \Modules\Core\App\Charts\ChartResult
     */
    public function average($request, $model, $column, $groupBy, $callback = null)
    {
        return $this->aggregate($request, $model, 'avg', $column, $groupBy, $callback);
    }

    /**
     * Return a presentation result showing the segments of a sum aggregate.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  string|null  $column
     * @param  string  $groupBy
     * @param  \Closure  $callback  query callback
     * @return \Modules\Core\App\Charts\ChartResult
     */
    public function sum($request, $model, $column, $groupBy, $callback = null)
    {
        return $this->aggregate($request, $model, 'sum', $column, $groupBy, $callback);
    }

    /**
     * Return a presentation result showing the segments of a max aggregate.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  string|null  $column
     * @param  string  $groupBy
     * @param  \Closure  $callback  query callback
     * @return \Modules\Core\App\Charts\ChartResult
     */
    public function max($request, $model, $column, $groupBy, $callback = null)
    {
        return $this->aggregate($request, $model, 'max', $column, $groupBy, $callback);
    }

    /**
     * Return a presentation result showing the segments of a min aggregate.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  string|null  $column
     * @param  string  $groupBy
     * @param  \Closure  $callback  query callback
     * @return \Modules\Core\App\Charts\ChartResult
     */
    public function min($request, $model, $column, $groupBy, $callback = null)
    {
        return $this->aggregate($request, $model, 'min', $column, $groupBy, $callback);
    }

    /**
     * Return a presentation result showing the segments of a aggregate.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  string  $function
     * @param  string  $column
     * @param  string  $groupBy
     * @param  \Closure  $callback  query callback
     * @return \Modules\Core\App\Charts\ChartResult
     */
    protected function aggregate($request, $model, $function, $column, $groupBy, $callback)
    {
        $query = $model instanceof Builder ? $model : (new $model)->newQuery();

        $wrappedColumn = $query->getQuery()->getGrammar()->wrap(
            $column ?? $query->getModel()->getQualifiedKeyName()
        );

        $wrappedGroupBy = $query->getQuery()->getGrammar()->wrap($groupBy);

        $query = $query->selectRaw("{$function}({$wrappedColumn}) as aggregate, {$wrappedGroupBy}");

        if ($this->queryByDate) {
            [$dateColumn, $unit] = $this->queryByDate;

            $startingDate = $this->getStartingDate($this->getCurrentRange($request), $unit);
            $endingDate = Carbon::asAppTimezone();
            $query->whereBetween($dateColumn, [$startingDate, $endingDate]);
        }

        if ($callback) {
            $query = $callback($query);
        }

        $results = $query->groupBy($groupBy)->get();

        return $this->result($results->mapWithKeys(function ($result) use ($groupBy) {
            return $this->formatResult($result, $groupBy);
        })->all());
    }

    /**
     * Format the aggregate result for the presentation.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $result
     */
    protected function formatResult($result, string $groupBy): array
    {
        $attribute = last(explode('.', $groupBy));
        $key = $result->{$attribute};

        if ($key instanceof \UnitEnum) {
            $key = $key->value;
        }

        return [$key => round($result->aggregate, 0)];
    }

    /**
     * Create a new presentation result
     */
    public function result(array $value): ChartResult
    {
        return new ChartResult(collect($value)->map(function (int|float $number) {
            return round($number, $this->roundingPrecision, $this->roundingMode);
        })->toArray());
    }

    /**
     * The element's component
     */
    public function component(): string
    {
        return 'presentation-chart';
    }

    /**
     * Determine for how many minutes the card value should be cached.
     */
    public function cacheFor(): DateTimeInterface
    {
        return now()->addMinutes(5);
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'horizontal' => $this->horizontal,
                'onlyInteger' => $this->onlyInteger,
                'axisYOffset' => $this->axisYOffset,
                'axisXOffset' => $this->axisXOffset,
            ]
        );
    }
}
