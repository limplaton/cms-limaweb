<?php
 

namespace Modules\Activities\App\Contracts;

interface Attendeeable
{
    /**
     * Get the person email address
     */
    public function getGuestEmail(): ?string;

    /**
     * Get the person displayable name
     */
    public function getGuestDisplayName(): string;

    /**
     * Get the notification that should be sent to the person when is added as guest
     *
     * @return \Illuminate\Mail\Mailable|\Illuminate\Notifications\Notification|string
     */
    public function getAttendeeNotificationClass();

    /**
     * Indicates whether the attending notification should be send to the guest
     */
    public function shouldSendAttendingNotification(Attendeeable $model): bool;
}
