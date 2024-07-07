<?php
 

namespace Modules\Activities\App\Concerns;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Modules\Activities\App\Contracts\Attendeeable;
use Modules\Core\App\Models\Model;

trait HasGuests
{
    /**
     * Get all of the guests for the activity.
     */
    public function guests(): BelongsToMany
    {
        return $this->belongsToMany(\Modules\Activities\App\Models\Guest::class, 'activity_guest');
    }

    /**
     * Save activity guests.
     *
     * @link https://stackoverflow.com/questions/34809469/laravel-many-to-many-relationship-to-multiple-models
     *
     * @param  \Modules\Activities\App\Contracts\Attendeeable[]  $guests
     */
    public function saveGuests(array $guests, bool $notify = true): array
    {
        $currentGuests = $this->guests()->with('guestable')->get();

        // First, we will check the guests that we need to detach by
        // checking if the current guestable does not exists in the actual provided guests
        $detachGuests = $currentGuests->filter(function ($currentGuests) use ($guests) {
            return ! collect($guests)->first(function ($guest) use ($currentGuests) {
                return $guest->getKey() === $currentGuests->guestable->getKey() &&
                $guest::class === $currentGuests->guestable::class;
            });
        });

        // Next we will check the new guests we need to attach by checking if the
        // provided guestable does not exists in the current guests
        $attachGuests = collect($guests)->filter(function ($guest) use ($currentGuests) {
            return ! $currentGuests->first(function ($currentGuests) use ($guest) {
                return $guest->getKey() === $currentGuests->guestable->getKey() &&
                $guest::class === $currentGuests->guestable::class;
            });
        })->all();

        $this->addGuest($attachGuests, $notify);

        $detachGuests->each->delete();

        if (count($attachGuests) || count($detachGuests)) {
            if (! static::isIgnoringTouch() && ! $this->wasChanged($this->getUpdatedAtColumn())) {
                $this->touch();
            }
        }

        return ['attached' => $attachGuests, 'detached' => $detachGuests->all()];
    }

    /**
     * Save activity guests without sending a notification.
     */
    public function saveGuestsSilently(array $guests): array
    {
        return $this->saveGuests($guests, false);
    }

    /**
     * Add new guest(s) to the activity.
     */
    public function addGuest(Attendeeable|array $guests, bool $notify = true): void
    {
        if ($guests instanceof Attendeeable) {
            $guests = [$guests];
        }

        // Todo, in future check if the actual guest exists
        foreach ($guests as $model) {
            $guest = $model->guests()->create([]);
            $guest->activities()->attach($this);

            if ($notify && $model->shouldSendAttendingNotification($model)) {
                $this->sendNotificationToAttendee($model);
            }
        }
    }

    /**
     * Send notification to the given attendee
     */
    protected function sendNotificationToAttendee(Attendeeable $guest): void
    {
        $notification = $guest->getAttendeeNotificationClass();

        if (method_exists($guest, 'notify') && is_a($notification, Notification::class, true)) {
            $guest->notify(new $notification($guest, $this));
        } elseif (is_a($notification, Mailable::class, true) && ! empty($email = $guest->getGuestEmail())) {
            Mail::to($email)->send(new $notification($guest, $this));
        }
    }

    /**
     * Check whether the given guest attends to the activity.
     */
    public function hasGuest(Model&Attendeeable $attendee): bool
    {
        return $this->guests->contains(function ($guest) use ($attendee) {
            return (int) $guest->guestable_id === (int) $attendee->getKey() && $attendee::class === $guest->guestable_type;
        });
    }
}
