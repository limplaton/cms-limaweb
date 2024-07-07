<?php
 

namespace Modules\Core\App\Contracts\Calendar;

use Illuminate\Database\Query\Expression;

interface DisplaysOnCalendar
{
    /**
     * Get the start date
     */
    public function getCalendarStartDate(): string;

    /**
     * Get the end date
     */
    public function getCalendarEndDate(): string;

    /**
     * Indicates whether the event is all day
     */
    public function isAllDay(): bool;

    /**
     * Get the displayable title for the calendar
     */
    public function getCalendarTitle(): string;

    /**
     * Get the calendar start date column name for query
     */
    public static function getCalendarStartColumnName(): string|Expression;

    /**
     * Get the calendar end date column name for query
     */
    public static function getCalendarEndColumnName(): string|Expression;
}
