<?php
 

namespace Modules\Core\App\Contracts\OAuth;

interface Calendarable
{
    /**
     * Get the OAuth account calendars
     *
     * @return \Modules\Core\App\Contracts\Calendar\Calendar[]
     */
    public function getCalendars();
}
