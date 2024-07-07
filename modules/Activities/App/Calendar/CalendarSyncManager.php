<?php
 

namespace Modules\Activities\App\Calendar;

use InvalidArgumentException;
use Modules\Activities\App\Models\Calendar;

class CalendarSyncManager
{
    /**
     * Create calendar synchronizer
     *
     * @return \Modules\Activities\App\Calendar\CalendarSynchronization&\Modules\Core\App\Contracts\Synchronization\Synchronizable
     */
    public static function createClient(Calendar $calendar)
    {
        $method = 'create'.ucfirst($calendar->connection_type).'Driver';

        if (! method_exists(new static, $method)) {
            throw new InvalidArgumentException(sprintf(
                'Unable to resolve [%s] driver for [%s].',
                $method,
                static::class
            ));
        }

        return self::$method($calendar);
    }

    /**
     * Create the Google calendar sync driver
     *
     *
     * @return \Modules\Activities\App\Calendar\GoogleCalendarSync
     */
    public static function createGoogleDriver(Calendar $calendar)
    {
        return new GoogleCalendarSync($calendar);
    }

    /**
     * Create the Outlook calendar sync driver
     *
     *
     * @return \Modules\Activities\App\Calendar\OutlookCalendarSync
     */
    public static function createOutlookDriver(Calendar $calendar)
    {
        return new OutlookCalendarSync($calendar);
    }
}
