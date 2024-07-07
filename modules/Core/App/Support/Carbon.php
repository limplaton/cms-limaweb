<?php
 

namespace Modules\Core\App\Support;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon as BaseCarbon;
use InvalidArgumentException;
use Modules\Core\App\Contracts\Localizeable;

class Carbon extends BaseCarbon
{
    /**
     * Convert the Carbon instance to app timezone
     *
     * E.q. Carbon::asCurrentTimezone()->inAppTimezone();
     *
     * @return static
     */
    public function inAppTimezone()
    {
        return $this->timezone(
            config('app.timezone')
        );
    }

    /**
     * 1. If date provided: Takes the given UTC date and converts to the current timezone
     * 2. If date not provided: Uses the current (now) UTC date
     * 3. If $user not provided: Uses current logged-in user
     * 4. If no logged in user: uses application timezone, equal to no.2
     *
     * Usually using in where queries when querying data from database when
     * the query dates must be converted from utc to user timezone and then to to application timezone
     * so we can know the exact UTC date of the user timezone
     *
     * For example, we have the 2021-12-15 15:00:00 in UTC date
     * but the logged in user uses America/New_York date, we must convert the UTC date to New York date
     * and then to application date so we can know the current New York in UTC date
     *
     * This is how timezoned dates should work we believe, we have UTC date, we have Timezone
     * we use the UTC date and the Timezone to convert the UTC date to the Timezone and then
     * the converted date can be easily converted to UTC (config('app.timezone')) using the inAppTimezone method
     *
     * The method should be used also for dates manipulations
     * e.q.
     *
     * Carbon::asCurrentTimezone($utcdate)
     *            ->subMinutes(30)
     *           ->inAppTimezone();
     *
     * Also is equivalent to:
     *
     * Carbon::createFromFormat(
     *    'Y-m-d H:i:s',
     *    '2021-12-15 00:00:00',
     *    'America/New_york'
     * );
     *
     * @see forAppTimezone
     * @see inAppTimezone
     *
     * @param  mixed  $time
     * @return \Modules\Core\App\Support\Carbon
     */
    public static function asCurrentTimezone($time = null, ?Localizeable $user = null)
    {
        return static::parse($time, tz()->current($user));
    }

    /**
     * Convert the given UTC date be used for the application
     * e.q. UTC date to New_York to UTC, in this case
     * we will know the New_York date in UTC
     *
     * @param  mixed  $time
     * @return \Modules\Core\App\Support\Carbon
     */
    public static function fromCurrentToAppTimezone($time, ?Localizeable $user = null)
    {
        return static::asCurrentTimezone($time, $user)->inAppTimezone();
    }

    /**
     * Get date from the current timezone as in app timezone
     *
     * @param  mixed  $time
     * @return \Modules\Core\App\Support\Carbon
     */
    public static function asAppTimezone($time = null, ?Localizeable $user = null)
    {
        return static::asCurrentTimezone($time, $user)->inAppTimezone();
    }

    /**
     * Get the timestamp in user timezone
     *
     * @param  mixed  $value
     * @param  null\Modules\Core\App\Contracts\Localizeable  $user
     * @return \Modules\Core\App\Support\Carbon
     */
    public static function inUserTimezone($value, ?Localizeable $user = null)
    {
        $timezone = tz()->current($user);

        return static::asDateTime($value)->timezone($timezone);
    }

    /**
     * Return a timestamp as DateTime object.
     *
     * @param  mixed  $value
     * @return \Modules\Core\App\Support\Carbon
     */
    public static function asDateTime($value)
    {
        // If this value is already a Carbon instance, we shall just return it as is.
        // This prevents us having to re-instantiate a Carbon instance when we know
        // it already is one, which wouldn't be fulfilled by the DateTime check.
        if ($value instanceof BaseCarbon || $value instanceof CarbonInterface) {
            return static::instance($value);
        }

        // If the value is already a DateTime instance, we will just skip the rest of
        // these checks since they will be a waste of time, and hinder performance
        // when checking the field. We will just return the DateTime right away.
        if ($value instanceof DateTimeInterface) {
            return static::parse(
                $value->format('Y-m-d H:i:s.u'),
                $value->getTimezone()
            );
        }

        // If this value is an integer, we will assume it is a UNIX timestamp's value
        // and format a Carbon object from this timestamp.
        if (is_numeric($value)) {
            return static::createFromTimestamp($value);
        }

        // If the value is in simply year, month, day format, we will instantiate the
        // Carbon instances from that format.
        if (static::isStandardDateFormat($value)) {
            return static::instance(static::createFromFormat('Y-m-d', $value)->startOfDay());
        }

        // Finally, we will just assume this date is in the default Y-m-d H:i:s
        try {
            $date = static::createFromFormat('Y-m-d H:i:s', $value);
        } catch (InvalidArgumentException) {
            $date = false;
        }

        return $date ?: static::parse($value);
    }

    /**
     * Check whether the given string is ISO 8601 date
     *
     * @param  mixed  $value
     * @return bool
     */
    public static function isISO8601($value)
    {
        if (! is_string($value) || empty($value)) {
            return false;
        }

        // @see https://www.designcise.com/web/tutorial/whats-the-difference-between-php-datetime-atom-and-datetime-iso8601
        // 'Y-m-d\\TH:i:sO' \DateTimeInterface::ISO8601 - deprected

        $ISO8601 = \DateTime::createFromFormat('Y-m-d\\TH:i:sO', $value);

        if ($ISO8601 && $ISO8601->format('Y-m-d\\TH:i:sO') == $value) {
            return true;
        }

        $ATOM = \DateTime::createFromFormat(\DateTimeInterface::ATOM, $value);

        if ($ATOM && $ATOM->format(\DateTimeInterface::ATOM) == $value) {
            return true;
        }

        // @link https://rgxdb.com/r/526K7G5W
        $pattern = '/^(?:[1-9]\d{3}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1\d|2[0-8])|(?:0[13-9]|1[0-2])-(?:29|30)|(?:0[13578]|1[02])-31)|(?:[1-9]\d(?:0[48]|[2468][048]|[13579][26])|(?:[2468][048]|[13579][26])00)-02-29)T(?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d(?:Z|[+-][01]\d:[0-5]\d)$/';

        return (bool) preg_match($pattern, $value);
    }

    /**
     * Determine if the given value is a standard date format.
     *
     * @param  string  $value
     * @return bool
     */
    public static function isStandardDateFormat($value)
    {
        return preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value);
    }
}
