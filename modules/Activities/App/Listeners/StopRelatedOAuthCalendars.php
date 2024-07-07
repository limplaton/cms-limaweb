<?php
 

namespace Modules\Activities\App\Listeners;

use Modules\Activities\App\Models\Calendar;
use Modules\Core\App\Common\OAuth\Events\OAuthAccountDeleting;

class StopRelatedOAuthCalendars
{
    /**
     * Stop the related calendars of the OAuth account when deleting.
     */
    public function handle(OAuthAccountDeleting $event): void
    {
        Calendar::with('synchronization')
            ->where('access_token_id', $event->account->id)
            ->get()
            ->each(function (Calendar $calendar) {
                $calendar->disableSync();
                $calendar->forceFill(['access_token_id' => null])->save();
            });
    }
}
