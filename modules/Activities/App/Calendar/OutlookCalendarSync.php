<?php
 

namespace Modules\Activities\App\Calendar;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Microsoft\Graph\Model\Event;
use Microsoft\Graph\Model\Event as EventModel;
use Microsoft\Graph\Model\Subscription;
use Modules\Activities\App\Events\CalendarSyncFinished;
use Modules\Activities\App\Models\Activity;
use Modules\Activities\App\Models\Calendar;
use Modules\Core\App\Common\Synchronization\Exceptions\InvalidSyncNotificationURLException;
use Modules\Core\App\Contracts\Synchronization\Synchronizable;
use Modules\Core\App\Contracts\Synchronization\SynchronizesViaWebhook;
use Modules\Core\App\Facades\MsGraph as Api;
use Modules\Core\App\Models\Synchronization;

class OutlookCalendarSync extends CalendarSynchronization implements Synchronizable, SynchronizesViaWebhook
{
    protected string $webHookUrl;

    /**
     * Initialize new OutlookCalendarSync class
     */
    public function __construct(protected Calendar $calendar)
    {
        $this->webHookUrl = URL::asAppUrl('webhook/outlook-calendar');
    }

    /**
     * Synchronize the data for the given synchronization
     */
    public function synchronize(Synchronization $synchronization): void
    {
        Api::connectUsing($this->calendar->email);

        try {
            $iterator = Api::immutable(
                fn () => Api::createCollectionGetRequest($this->createEndpoint())->setReturnType(EventModel::class)
            );

            $events = Api::iterateCollectionRequest($iterator);

            $changesPerformed = $this->processChangedEvents($events ?? []);

            $synchronization->updateLastSyncDate();

            CalendarSyncFinished::dispatchIf($changesPerformed, $synchronization->synchronizable);
        } catch (IdentityProviderException) {
            $this->calendar->oAuthAccount->setAuthRequired();
        }
    }

    /**
     * Iterage over the changed events
     *
     * @param  \Microsoft\Graph\Model\Event[]  $events
     */
    protected function processChangedEvents(array $events): bool
    {
        foreach ($events as $event) {
            [$model, $guestsUpdated] = $this->processViaChange(
                $this->attributesFromEvent($event),
                $this->determineUser($event->getOrganizer()?->getEmailAddress()?->getAddress(), $this->calendar->user),
                $event->getId(),
                $event->getICalUId(),
                $this->calendar
            );

            if ($model->wasRecentlyCreated || $model->wasChanged() || $guestsUpdated) {
                $changesPerformed = true;
            }
        }

        return $changesPerformed ?? false;
    }

    /**
     * Create attributes from event
     */
    protected function attributesFromEvent(Event $event): array
    {
        $dueDate = Carbon::parse($event->getStart()->getDateTime());
        $endDate = Carbon::parse($event->getEnd()->getDateTime());
        $isAllDay = $event->getIsAllDay();

        return [
            'title' => $event->getSubject() ?? '(No Title)',
            'description' => $event->getBody()->getContent(),
            'due_date' => $dueDate->format('Y-m-d'),
            'due_time' => ! $isAllDay ? $dueDate->format('H:i').':00' : null,
            'end_date' => ($isAllDay ? $endDate->sub(1, 'second') : $endDate)->format('Y-m-d'),
            'end_time' => ! $isAllDay ? $endDate->format('H:i').':00' : null,
            'reminder_minutes_before' => $event->getReminderMinutesBeforeStart(),
            'guests' => collect($event->getAttendees())->map(function (array $attendee) {
                return [
                    'email' => $attendee['emailAddress']['address'],
                    'name' => $attendee['emailAddress']['name'],
                ];
            })->all(),
        ];
    }

    /**
     * Subscribe for changes for the given synchronization
     */
    public function watch(Synchronization $synchronization): void
    {
        $this->handleRequestExceptions(function () use ($synchronization) {
            try {
                $subscription = $this->createSubscriptionInstance($synchronization)->getProperties();

                $subscription = Api::immutable(
                    fn () => Api::createPostRequest('/subscriptions', $subscription)->setReturnType(Subscription::class)->execute()
                );

                $synchronization->markAsWebhookSynchronizable(
                    $subscription->getId(),
                    $subscription->getExpirationDateTime(),
                );
            } catch (ClientException $e) {
                // We will throw an exceptions for invalid URL and won't allow the
                // user to sync without valid URL as the Outlook synchronization works only
                // with webhooks and cannot use the polling method as we cannot detect deleted events when polling
                if ($this->isInvalidExceptionUrlMessage($e->getMessage())) {
                    throw new InvalidSyncNotificationURLException;
                }

                throw $e;
            }
        });
    }

    /**
     * Unsubscribe from changes for the given synchronization
     */
    public function unwatch(Synchronization $synchronization): void
    {
        // perhaps subscription for some reason not created? e.q. for notificationUrl validation failed
        if ($resourceId = $synchronization->resource_id) {
            $this->handleRequestExceptions(function () use ($synchronization, $resourceId) {
                Api::immutable(
                    fn () => Api::createDeleteRequest('/subscriptions/'.$resourceId)->execute()
                );

                $synchronization->unmarkAsWebhookSynchronizable();
            });
        }
    }

    /**
     * Update event in the calendar from the given activity
     */
    public function updateEvent(Activity $activity, string $eventId): void
    {
        $this->handleRequestExceptions(function () use ($activity, $eventId) {
            $endpoint = $this->endpoint('/'.$eventId);
            $payload = OutlookEventPayload::make($activity);

            Api::immutable(
                fn () => Api::createPatchRequest($endpoint, $payload)->execute()
            );
        });
    }

    /**
     * Create event in the calendar from the given activity
     */
    public function createEvent(Activity $activity): void
    {
        $this->handleRequestExceptions(function () use ($activity) {
            $endpoint = $this->endpoint();
            $payload = new OutlookEventPayload($activity);

            $event = Api::immutable(
                fn () => Api::createPostRequest($endpoint, $payload)->setReturnType(EventModel::class)->execute()
            );

            $activity->addSynchronization($event->getId(), $this->calendar->getKey(), [
                'icaluid' => $event->getICalUId(),
            ]);
        });
    }

    /**
     * Update event from the calendar for the given activity
     */
    public function deleteEvent(int $activityId, string $eventId): void
    {
        $this->handleRequestExceptions(function () use ($activityId, $eventId) {
            $endpoint = $this->endpoint('/'.$eventId);

            try {
                Api::immutable(fn () => Api::createDeleteRequest($endpoint)->execute());
            } catch (RequestException $e) {
                // https://stackoverflow.com/questions/55875130/calls-to-events-returning-error-this-operation-does-not-support-binding-to-a
                if (! str_contains($e->getMessage(), 'This operation does not support binding to a non-calendar folder')) {
                    throw $e;
                }
            }

            Activity::find($activityId)?->deleteSynchronization($eventId, $this->calendar->getKey());
        });
    }

    /**
     * Prepare the endpoint to retrieve the events
     */
    protected function createEndpoint(): string
    {
        $startFrom = new \DateTime($this->calendar->startSyncFrom());

        $endpoint = $this->endpoint();

        $endpoint .= '?$filter=createdDateTime ge '.$startFrom->format('Y-m-d\TH:i:s\Z');
        $endpoint .= ' and type eq \'singleInstance\'';
        $endpoint .= ' and isDraft eq false';
        // There are times when I have a personal appointment during the work day that needs to be on my work calendar but not synced to .
        // Having the ability to exclude calendar items marked as Private would solve this problem.
        $endpoint .= ' and sensitivity ne \'private\'';

        return $endpoint;
    }

    /**
     * Helper function to handle the requests common exception
     */
    protected function handleRequestExceptions(\Closure $callable): void
    {
        Api::connectUsing($this->calendar->email);

        try {
            $callable();
        } catch (ClientException $e) {
            throw_if($e->getCode() !== 404, $e);
        } catch (IdentityProviderException $e) {
            $this->calendar->oAuthAccount->setAuthRequired();
        }
    }

    /**
     * Create new Microsoft Subscription instance
     */
    protected function createSubscriptionInstance(Synchronization $synchronization): Subscription
    {
        return (new Subscription)->setChangeType('created,updated,deleted')
            ->setNotificationUrl($this->webHookUrl)
            ->setClientState($synchronization->id) // uuid;
            // https://docs.microsoft.com/en-us/graph/api/resources/subscription?view=graph-rest-1.0#maximum-length-of-subscription-per-resource-type
            ->setExpirationDateTime(now()->addDays(2))
            ->setResource($this->endpoint());
    }

    /**
     * Check whether the given exception message is invalid url
     */
    protected function isInvalidExceptionUrlMessage(string $message): bool
    {
        return Str::of($message)->lower()->contains([
            'invalid notification url',
            'subscription validation request failed',
            '\'http\' is not supported',
            'the remote name could not be resolved',
        ]);
    }

    /**
     * Create endpoint for the calendar
     */
    protected function endpoint(string $glue = ''): string
    {
        return '/me/calendars/'.$this->calendar->calendar_id.'/events'.$glue;
    }
}
