<?php
 

namespace Modules\Core\App\Common\Calendar\Google;

use Modules\Core\App\Common\Calendar\AbstractCalendar;

class Calendar extends AbstractCalendar
{
    /**
     * Get the calendar ID.
     */
    public function getId(): string
    {
        return $this->getEntity()->getId();
    }

    /**
     * Get the calendar title.
     */
    public function getTitle(): string
    {
        return $this->getEntity()->getSummary();
    }

    /**
     * Check whether the calendar is default.
     */
    public function isDefault(): bool
    {
        return $this->getEntity()->getPrimary() ?: false;
    }
}
