<?php
 

namespace Modules\Core\App\Common\Calendar\Google;

use Google\Service\Exception as GoogleServiceException;
use Modules\Core\App\Common\Calendar\Exceptions\UnauthorizedException;
use Modules\Core\App\Common\OAuth\AccessTokenProvider;
use Modules\Core\App\Contracts\OAuth\Calendarable;
use Modules\Core\App\Facades\Google as Client;

class GoogleCalendar implements Calendarable
{
    /**
     * Initialize new GoogleCalendar instance.
     */
    public function __construct(protected AccessTokenProvider $token)
    {
        Client::connectUsing($token->getEmail());
    }

    /**
     * Get the available calendars.
     *
     * @return \Modules\Core\App\Contracts\Calendar\Calendar[]
     */
    public function getCalendars()
    {
        try {
            return collect(Client::calendar()->list())
                ->mapInto(Calendar::class)
                ->all();
        } catch (GoogleServiceException $e) {
            $message = $e->getErrors()[0]['message'] ?? $e->getMessage();

            if ($e->getCode() == 403) {
                throw new UnauthorizedException($message, $e->getCode(), $e);
            }

            throw $e;
        }
    }
}
