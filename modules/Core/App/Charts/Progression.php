<?php
 

namespace Modules\Core\App\Charts;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\App\Facades\Timezone;
use Modules\Core\App\Support\Carbon;

abstract class Progression extends Chart
{
    /**
     * Count results aggregate over months
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  string|null  $column
     * @return \Modules\Core\App\Charts\ChartResult
     */
    public function countByMonths($request, $model, $column = null)
    {
        return $this->count($request, $model, self::BY_MONTHS, $column);
    }

    /**
     * Count results aggregate over weeks
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  string|null  $column
     * @return \Modules\Core\App\Charts\ChartResult
     */
    public function countByWeeks($request, $model, $column = null)
    {
        return $this->count($request, $model, self::BY_WEEKS, $column);
    }

    /**
     * Count results aggregate over days
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  string|null  $column
     * @return \Modules\Core\App\Charts\ChartResult
     */
    public function countByDays($request, $model, $column = null)
    {
        return $this->count($request, $model, self::BY_DAYS, $column);
    }

    /**
     * Count results aggregate over specific time
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  string  $unit
     * @param  string|null  $dateColumn
     * @return \Modules\Core\App\Charts\ChartResult
     */
    public function count($request, $model, $unit, $dateColumn = null)
    {
        $qualifiedKeyName = with(
            $model instanceof Builder ? $model->getModel() : new $model,
            fn ($instance) => $instance->getQualifiedKeyName()
        );

        return $this->aggregate($request, $model, $unit, 'count', $qualifiedKeyName, $dateColumn);
    }

    /**
     * Average results aggregate over months
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  string  $column
     * @param  string|null  $dateColumn
     * @return \Modules\Core\App\Charts\ChartResult
     */
    public function averageByMonths($request, $model, $column, $dateColumn = null)
    {
        return $this->aggregate($request, $model, self::BY_MONTHS, 'avg', $column, $dateColumn);
    }

    /**
     * Average results aggregate over weeks
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  string  $column
     * @param  string|null  $dateColumn
     * @return \Modules\Core\App\Charts\ChartResult
     */
    public function averageByWeeks($request, $model, $column, $dateColumn = null)
    {
        return $this->aggregate($request, $model, self::BY_WEEKS, 'avg', $column, $dateColumn);
    }

    /**
     * Average results aggregate over days
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  string  $column
     * @param  string|null  $dateColumn
     * @return \Modules\Core\App\Charts\ChartResult
     */
    public function averageByDays($request, $model, $column, $dateColumn = null)
    {
        return $this->aggregate($request, $model, self::BY_DAYS, 'avg', $column, $dateColumn);
    }

    /**
     * Average results aggregate over time
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  string  $unit
     * @param  string  $column
     * @param  string|null  $dateColumn
     * @return \Modules\Core\App\Charts\ChartResult
     */
    public function average($request, $model, $unit, $column, $dateColumn = null)
    {
        return $this->aggregate($request, $model, $unit, 'avg', $column, $dateColumn);
    }

    /**
     * Sum results aggregate over months
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  string  $column
     * @param  string|null  $dateColumn
     * @return \Modules\Core\App\Charts\ChartResult
     */
    public function sumByMonths($request, $model, $column, $dateColumn = null)
    {
        return $this->aggregate($request, $model, self::BY_MONTHS, 'sum', $column, $dateColumn);
    }

    /**
     * Sum results aggregate over weeks
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  string  $column
     * @param  string|null  $dateColumn
     * @return \Modules\Core\App\Charts\ChartResult
     */
    public function sumByWeeks($request, $model, $column, $dateColumn = null)
    {
        return $this->aggregate($request, $model, self::BY_WEEKS, 'sum', $column, $dateColumn);
    }

    /**
     * Sum results aggregate over days
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  string  $column
     * @param  string|null  $dateColumn
     * @return \Modules\Core\App\Charts\ChartResult
     */
    public function sumByDays($request, $model, $column, $dateColumn = null)
    {
        return $this->aggregate($request, $model, self::BY_DAYS, 'sum', $column, $dateColumn);
    }

    /**
     * Sum results aggregate over time
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  string  $unit
     * @param  string  $column
     * @param  string|null  $dateColumn
     * @return \Modules\Core\App\Charts\ChartResult
     */
    public function sum($request, $model, $unit, $column, $dateColumn = null)
    {
        return $this->aggregate($request, $model, $unit, 'sum', $column, $dateColumn);
    }

    /**
     * Return a value result showing a aggregate over time
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  string  $unit
     * @param  string  $function
     * @param  string  $column
     * @param  string  $dateColumn
     * @return \Modules\Core\App\Charts\ChartResult
     */
    protected function aggregate($request, $model, $unit, $function, $column, $dateColumn = null)
    {
        $query = $model instanceof Builder ? $model : (new $model)->newQuery();

        $dateColumn = $dateColumn ?: $query->getModel()->getCreatedAtColumn();
        $range = $this->getCurrentRange($request);

        $startingDate = $this->getStartingDate($range, $unit);
        $endingDate = Carbon::asAppTimezone();

        $expression = $this->determineAggragateExpression(
            $query->getModel()->getConnection()->getDriverName(),
            $dateColumn,
            $unit
        );

        $column = $query->getModel()->getGrammar()->wrap($column);

        $results = $query->selectRaw(DB::raw("{$expression} as date_result, {$function}({$column}) as aggregate"))
            ->groupBy(DB::raw($expression))
            ->orderBy('date_result')
            ->whereBetween($dateColumn, [$startingDate, $endingDate])
            ->get();

        $results = array_merge(
            $this->getPossibleDateResults($startingDate->copy(), $endingDate->copy(), $unit),
            $results->mapWithKeys(function ($result) use ($unit) {
                return [$this->formatLabelByDate(
                    $result->date_result,
                    $unit
                ) => round($result->aggregate, 0)];
            })->all()
        );

        if (count($results) > $range) {
            array_shift($results);
        }

        return $this->result($results);
    }

    /**
     * For date for the label that will be shown on the chart
     */
    protected function formatLabelByDate(string|Carbon $date, string $unit): string
    {
        if ($date instanceof Carbon) {
            $date = $date->format('Y-m-d');
        }

        $dayCallback = function () use ($date) {
            $newDate = Carbon::createFromFormat('Y-m-d', $date);

            return $newDate->format('F').' '.$newDate->format('j').', '.$newDate->format('Y');
        };

        return match ($unit) {
            'month' => $this->formatMonthDate($date),
            'week' => $this->formatWeekDate($date),
            'day' => $dayCallback(),
            default => $date,
        };
    }

    /**
     * Create a new progression chart result
     */
    public function result(array $value): ChartResult
    {
        return new ChartResult($value);
    }

    /**
     * Determine for how many minutes the card value should be cached.
     */
    public function cacheFor(): DateTimeInterface
    {
        return now()->addMinutes(5);
    }

    /**
     * Format date for the label that will be shown on the chart
     */
    protected function formatMonthDate(string $date): string
    {
        [$year, $month] = explode('-', $date);
        $newDate = Carbon::createFromDate((int) $year, (int) $month, 1);

        return $newDate->format('F').' '.$newDate->format('Y');
    }

    /**
     * Format the aggregate week result date into a proper string
     */
    protected function formatWeekDate(string $result): string
    {
        [$year, $week] = explode('-', $result);

        $isoDate = (new \DateTime)->setISODate($year, $week)->setTime(0, 0);

        [$startingDate, $endingDate] = [
            Carbon::instance($isoDate),
            Carbon::instance($isoDate)->endOfWeek(),
        ];

        return __($startingDate->format('F')).' '.$startingDate->format('j').' - '.
               __($endingDate->format('F')).' '.$endingDate->format('j');
    }

    /**
     * Get all possible dates for the charts
     * Continuous timeline including dates without data
     */
    protected function getPossibleDateResults(Carbon $startingDate, Carbon $endingDate, string $unit): array
    {
        $nextDate = $startingDate;
        $timezone = Timezone::current();

        $nextDate = $startingDate->setTimezone($timezone);
        $endingDate = $endingDate->setTimezone($timezone);

        $possibleDateResults[$this->formatLabelByDate($nextDate, $unit)] = 0;

        while ($nextDate->lt($endingDate)) {
            if ($unit === self::BY_MONTHS) {
                $nextDate = $nextDate->addMonths(1);
            } elseif ($unit === self::BY_WEEKS) {
                $nextDate = $nextDate->addWeeks(1);
            } elseif ($unit === self::BY_DAYS) {
                $nextDate = $nextDate->addDays(1);
            }

            if ($nextDate->lte($endingDate)) {
                $possibleDateResults[
                    $this->formatLabelByDate($nextDate, $unit)
                ] = 0;
            }
        }

        return $possibleDateResults;
    }

    /**
     * Get the expressions for the query based on the given driver
     *
     * @throws \InvalidArgumentException
     */
    protected function determineAggragateExpression(string $driver, string $column, string $unit): string
    {
        return match ($driver) {
            'mysql', 'mariadb' => $this->determineAggragateExpressionWhenMysqlOrMariaDB($unit, $column),
            'pgsql' => $this->determineAggragateExpressionWhenPostgre($unit, $column),
            default => throw new InvalidArgumentException('Some of the charts are not supported for this database driver.')
        };
    }

    /**
     * Determine the expression when the driver is mysql or mariadb
     */
    protected function determineAggragateExpressionWhenMysqlOrMariaDB(string $unit, string $column): string
    {
        return match ($unit) {
            'month' => "DATE_FORMAT({$column}, '%Y-%m')",
            'week' => "DATE_FORMAT({$column}, '%x-%v')",
            'day' => "DATE_FORMAT({$column}, '%Y-%m-%d')",
        };
    }

    /**
     * Determine the expression when the driver is postgres
     */
    protected function determineAggragateExpressionWhenPostgre(string $unit, string $column): string
    {
        return match ($unit) {
            'month' => "to_char($column, 'YYYY-MM')",
            'week' => "to_char($column, 'IYYY-IW')",
            'day' => "to_char($column, 'YYYY-MM-DD')",
        };
    }

    /**
     * The element's component
     */
    public function component(): string
    {
        return 'progression-chart';
    }
}
