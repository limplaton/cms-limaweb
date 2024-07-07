<?php
 

namespace Modules\Core\App\Support;

use InvalidArgumentException;

trait ProvidesBetweenArgumentsViaString
{
    /**
     * Get between arguments via string
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getBetweenArguments(string $value)
    {
        $tz = tz()->current();
        $now = Carbon::asCurrentTimezone();

        $retVal = match ($value) {
            'today' => [$now->copy()->startOfDay(), $now->endOfDay()],
            'yesterday' => [$now->copy()->yesterday($tz)->startOfDay(), $now->yesterday($tz)->endOfDay()],
            'next_day' => [$now->copy()->tomorrow($tz)->startOfDay(), $now->tomorrow($tz)->endOfDay()],
            'this_week' => [$now->copy()->startOfWeek(), $now->endOfWeek()],
            'last_week' => [$now->copy()->subWeek()->startOfWeek(), $now->subWeek()->endOfWeek()],
            'next_week' => [$now->copy()->addWeek()->startOfWeek(), $now->addWeek()->endOfWeek()],
            'this_month' => [$now->copy()->startOfMonth(), $now->endOfMonth()],
            // https://github.com/briannesbitt/Carbon/issues/639
            'last_month' => [
                $now->copy()->startOfMonth()->subMonth()->startOfMonth(),
                $now->startOfMonth()->subMonth()->endOfMonth(),
            ],
            'next_month' => [$now->copy()->startOfMonth()->addMonth(), $now->startOfMonth()->addMonth()->endOfMonth()],
            'this_quarter' => [$now->copy()->startOfQuarter(), $now->endOfQuarter()],
            'last_quarter' => [$now->copy()->subQuarter()->startOfQuarter(), $now->subQuarter()->endOfQuarter()],
            'next_quarter' => [$now->copy()->addQuarter()->startOfQuarter(), $now->addQuarter()->endOfQuarter()],
            'this_year' => [$now->copy()->startOfYear(), $now->endOfYear()],
            'last_year' => [$now->copy()->subYear()->startOfYear(), $now->subYear()->endOfYear()],
            'next_year' => [$now->copy()->addYear()->startOfYear(), $now->addYear()->endOfYear()],
            'last_7_days' => [$now->copy()->subDays(7)->startOfDay(), $now->endOfDay()],
            'last_14_days' => [$now->copy()->subDays(14)->startOfDay(), $now->endOfDay()],
            'last_30_days' => [$now->copy()->subDays(30)->startOfDay(), $now->endOfDay()],
            'last_60_days' => [$now->copy()->subDays(60)->startOfDay(), $now->endOfDay()],
            'last_90_days' => [$now->copy()->subDays(90)->startOfDay(), $now->endOfDay()],
            'last_365_days' => [$now->copy()->subDays(365)->startOfDay(), $now->endOfDay()],
            default => throw new InvalidArgumentException('Invalid date type.')
        };

        $retVal[0] = $retVal[0]->inAppTimezone();
        $retVal[1] = $retVal[1]->inAppTimezone();

        return $retVal;
    }
}
