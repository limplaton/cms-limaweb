<?php
 

namespace Modules\Core\App\Common\Calendar;

use InvalidArgumentException;
use Modules\Core\App\Common\Calendar\Google\GoogleCalendar;
use Modules\Core\App\Common\Calendar\Outlook\OutlookCalendar;
use Modules\Core\App\Common\OAuth\AccessTokenProvider;
use Modules\Core\App\Contracts\OAuth\Calendarable;

class CalendarManager
{
    /**
     * Create calendar client.
     */
    public static function createClient(string $connectionType, AccessTokenProvider $token): Calendarable
    {
        $method = 'create'.ucfirst($connectionType).'Driver';

        if (! method_exists(new static, $method)) {
            throw new InvalidArgumentException(sprintf(
                'Unable to resolve [%s] driver for [%s].',
                $method,
                static::class
            ));
        }

        return self::$method($token);
    }

    /**
     * Create the Google calendar driver.
     */
    public static function createGoogleDriver(AccessTokenProvider $token): GoogleCalendar&Calendarable
    {
        return new GoogleCalendar($token);
    }

    /**
     * Create the Outlook calendar driver.
     */
    public static function createOutlookDriver(AccessTokenProvider $token): OutlookCalendar&Calendarable
    {
        return new OutlookCalendar($token);
    }
}
