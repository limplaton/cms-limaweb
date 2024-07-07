<?php
 

namespace Modules\Activities\App\Calendar;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Modules\Activities\App\Models\Activity;
use Modules\Activities\App\Models\Calendar;
use Modules\Contacts\App\Models\Contact;
use Modules\Core\App\Facades\ChangeLogger;
use Modules\Core\App\Models\OAuthAccount;
use Modules\Users\App\Models\User;

class CalendarSynchronization
{
    /**
     * Create or update activity
     */
    protected function processViaChange(array $data, User $user, string|int $eventId, string $iCalUID, Calendar $calendar): array
    {
        ChangeLogger::disable();

        $guests = Arr::pull($data, 'guests', []);

        $model = tap($this->getActivityInstanceFromEvent($eventId, $data, $user, $calendar))->save();

        $syncAttributes = ['icaluid' => $iCalUID];

        if ($model->wasRecentlyCreated) {
            $model->addSynchronization($eventId, $calendar->getKey(), $syncAttributes);
        } else {
            // in case of previously synced events without icaluid, perform update
            $model->updateSynchronization($eventId, $calendar->getKey(), $syncAttributes);
        }

        $guestsChanges = $this->saveGuests(
            $this->determineGuestsForSaving($guests, $user),
            $model
        );

        ChangeLogger::enable();

        return [$model, count(array_filter($guestsChanges)) !== 0];
    }

    /**
     * Get activity instance from event
     */
    protected function getActivityInstanceFromEvent(int|string $eventId, array $attributes, User $user, Calendar $calendar): Activity
    {
        $instance = Activity::byEventSyncId($eventId)->first() ?? new Activity;

        $instance->calendarable = false;

        if ($instance->trashed()) {
            $instance->restore();
        }

        $attributes = array_merge($attributes, ! $instance->exists ? [
            'user_id' => $user->getKey(),
            'created_by' => $user->getKey(),
            'owner_assigned_date' => now(),
            'activity_type_id' => $calendar->activity_type_id,
        ] : []);

        return $instance->forceFill($attributes);
    }

    /**
     * Persists the guests in storage
     */
    protected function saveGuests(Collection $guests, Activity $activity): array
    {
        $changes = $activity->saveGuestsSilently($guests->all());
        $associations = collect([]);

        // We will check if the activity is new, if yes, we will associate
        // the activity to all contacts that were added as attendee
        if ($activity->wasRecentlyCreated) {
            $associations = $associations->merge($guests->whereInstanceOf(Contact::class));
        } else {
            // If the activity is not new, we will only associate the newly guest contacts
            // we associate only the newly guest contacts because the user may have dissociated
            // any contacts from the activity after it was added and we should respect that not re-associate the contacts again
            $associations = $associations->merge(
                collect($changes['attached'])->whereInstanceOf(Contact::class)
            );
        }

        if ($associations->isNotEmpty()) {
            $activity->contacts()->syncWithoutDetaching($associations->pluck('id'));
        }

        return $changes;
    }

    /**
     * Determine the guests for saving when processing the changed events
     */
    protected function determineGuestsForSaving(array $guests, User $user): Collection
    {
        return collect($guests)->reject(
            fn ($attendee) => empty($attendee['email'])
        )->map(function ($attendee) use ($user) {
            if ($guest = User::where('email', $attendee['email'])->first()) {
                return $guest;
            }

            if ($guest = Contact::where('email', $attendee['email'])->first()) {
                return $guest;
            }

            return Contact::unguarded(function () use ($attendee, $user) {
                $firstName = $attendee['email'];

                if ($attendee['name']) {
                    $nameParts = explode(' ', $attendee['name']);
                    $firstName = $nameParts[0];
                    $lastName = $nameParts[1] ?? null;
                }

                return tap(new Contact([
                    'first_name' => $firstName,
                    'last_name' => $lastName ?? null,
                    'email' => $attendee['email'],
                    'created_by' => $user->getKey(),
                    'user_id' => $user->getKey(),
                ]), function ($contact) use ($user) {
                    $contact->save();
                    $this->addChangelogGuestCreatedAsContact($contact, $user);
                });
            });
        });
    }

    /**
     * Add changelog when a guest is created as contact
     */
    protected function addChangelogGuestCreatedAsContact(Contact $contact, User $user): void
    {
        $properties = [
            'icon' => 'Calendar',
            'lang' => [
                'key' => 'activities::calendar.timeline.imported_via_calendar_attendee',
                'attrs' => [
                    'user' => $user->name,
                ],
            ],
        ];

        ChangeLogger::forceLogging()
            ->useModelLog()
            ->on($contact)
            ->byAnonymous()
            ->generic()
            ->withProperties($properties)->log();
    }

    /**
     * Determine the activity user from the given email address
     */
    protected function determineUser(?string $email, ?User $default = null): ?User
    {
        if (empty($email)) {
            return $default;
        }

        // We will get the activity user from the given email, the email should be the organizer/creator of the event
        return OAuthAccount::where('email', $email)->first()?->user ?? $default;
    }
}
