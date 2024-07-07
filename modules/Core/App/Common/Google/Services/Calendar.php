<?php
 

namespace Modules\Core\App\Common\Google\Services;

use Google\Client;
use Google\Service\Calendar as GoogleCalendarService;

class Calendar extends Service
{
    /**
     * Initialize new Calendar service instance.
     */
    public function __construct(Client $client)
    {
        parent::__construct($client, GoogleCalendarService::class);
    }

    /**
     * List all available user calendars
     *
     * @return \Google\Service\Calendar\CalendarListEntry[]
     */
    public function list()
    {
        /** @var Google\Service\Calendar */
        $service = $this->service;

        $calendars = [];
        $nextPage = null;

        do {
            $calendarList = $service->calendarList->listCalendarList([
                'pageToken' => $nextPage,
            ]);

            foreach ($calendarList->getItems() as $calendar) {
                $calendars[] = $calendar;
            }
        } while (($nextPage = $calendarList->getNextPageToken()));

        return $calendars;
    }

    /**
     * Get calendar by id
     *
     * @param  string  $id
     * @return \Google\Service\Calendar\CalendarListEntry
     */
    public function get($id)
    {
        /** @var Google\Service\Calendar */
        $service = $this->service;

        return $service->calendars->get('me', $id);
    }
}
