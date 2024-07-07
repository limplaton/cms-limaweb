<?php
 

namespace Modules\Core\App\Common\Calendar;

use Modules\Core\App\Contracts\Calendar\Calendar as CalendarInterface;
use Modules\Core\App\Support\AbstractMask;

abstract class AbstractCalendar extends AbstractMask implements CalendarInterface
{
    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * toArray
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'is_default' => $this->isDefault(),
        ];
    }
}
