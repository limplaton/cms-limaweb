<?php
 

namespace Modules\Core\App\Common\Calendar\Outlook;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Microsoft\Graph\Model\Calendar as CalendarModel;
use Modules\Core\App\Common\OAuth\AccessTokenProvider;
use Modules\Core\App\Common\OAuth\Exceptions\ConnectionErrorException;
use Modules\Core\App\Contracts\OAuth\Calendarable;
use Modules\Core\App\Facades\MsGraph as Api;

class OutlookCalendar implements Calendarable
{
    /**
     * Initialize new OutlookCalendar instance.
     */
    public function __construct(protected AccessTokenProvider $token)
    {
        Api::connectUsing($token);
    }

    /**
     * Get the available calendars
     *
     * @return \Modules\Core\App\Contracts\Calendar\Calendar[]
     */
    public function getCalendars()
    {
        $iterator = Api::createCollectionGetRequest('/me/calendars')->setReturnType(CalendarModel::class);

        return collect($this->iterateRequest($iterator))
            ->mapInto(Calendar::class)
            ->all();
    }

    /**
     * Itereate the request pages and get all the data
     *
     * @param  \Iterator  $iterator
     * @return array
     */
    protected function iterateRequest($iterator)
    {
        try {
            return Api::iterateCollectionRequest($iterator);
        } catch (IdentityProviderException $e) {
            throw new ConnectionErrorException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
