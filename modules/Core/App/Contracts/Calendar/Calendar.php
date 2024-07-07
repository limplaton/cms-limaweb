<?php
 

namespace Modules\Core\App\Contracts\Calendar;

interface Calendar
{
    /**
     * Get the calendar ID.
     */
    public function getId(): int|string;

    /**
     * Get the calendar title.
     */
    public function getTitle(): string;

    /**
     * Check whether the calendar is default.
     */
    public function isDefault(): bool;
}
