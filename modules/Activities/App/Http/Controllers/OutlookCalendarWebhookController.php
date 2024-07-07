<?php
 

namespace Modules\Activities\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Activities\App\Models\Activity;
use Modules\Core\App\Models\Synchronization;

class OutlookCalendarWebhookController extends Controller
{
    /**
     * Handle the webhook request.
     */
    public function handle(Request $request): Response
    {
        // https://docs.microsoft.com/en-us/graph/webhooks#notification-endpoint-validation
        if ($request->has('validationToken')) {
            return response($request->validationToken, 200)->header('Content-Type', 'text/plain');
        }

        // https://docs.microsoft.com/en-us/graph/webhooks#change-notification-example
        $synchronizationIds = [];

        foreach ($request->value as $eventChange) {
            try {
                $synchronization = Synchronization::findOrFail($request->value[0]['clientState']);

                // We will remove the deleted event here because there is no option to
                // track the deleted events via the OutlookCalendarSync class
                if (strtolower($eventChange['changeType']) === 'deleted') {
                    $this->handleDeletedEvent(
                        $eventChange['resourceData']['id'],
                        $synchronization->synchronizable->getKey(),
                    );

                    continue;
                }

                $synchronizationIds[] = $synchronization->id;
            } catch (ModelNotFoundException) {
            }
        }

        // Not sure if this can happen
        $synchronizationIds = array_unique($synchronizationIds);

        if (count($synchronizationIds) > 0) {
            Synchronization::withoutOAuthAuthenticationRequired()
                ->whereIn('id', $synchronizationIds)
                ->get()
                ->each
                ->ping();
        }

        // https://docs.microsoft.com/en-us/graph/webhooks#processing-the-change-notification
        return response('', 202);
    }

    /**
     * Handle deleted event
     *
     * @param  string  $eventId
     * @param  int  $calendarId
     * @return void
     */
    protected function handleDeletedEvent($eventId, $calendarId)
    {
        if ($activity = Activity::byEventSyncId($eventId)->first()) {
            if ($activity->user->can('delete', $activity)) {
                try {
                    $activity->calendarable = false;
                    $activity->delete();
                } catch (ModelNotFoundException) {
                }
            } else {
                $activity->deleteSynchronization($eventId, $calendarId);
            }
        }
    }
}
