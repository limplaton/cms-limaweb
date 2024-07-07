<?php
 

namespace Modules\Core\App\Notifications;

use Modules\Core\App\Contracts\HasNotificationsSettings;

class NotificationsManager
{
    /**
     * All of the registered notifications.
     */
    protected static array $registered = [];

    /**
     * Indicates if all the notifications are disabled.
     */
    protected static bool $disabled = false;

    /**
     * Disable all of the notifications.
     */
    public static function disable(): void
    {
        static::$disabled = true;
    }

    /**
     * After disabling, enable all of the notifications again.
     */
    public static function enable(): void
    {
        static::$disabled = false;
    }

    /**
     * Check if all of the notifications are disabled.
     */
    public static function disabled(): bool
    {
        return static::$disabled;
    }

    /**
     * Register the given notifications.
     */
    public static function register(array $notifications): void
    {
        static::$registered = array_unique(
            array_merge(static::$registered, $notifications)
        );
    }

    /**
     * Get all the notifications information for front-end.
     */
    public static function preferences(?HasNotificationsSettings $notifiable = null): array
    {
        return collect(static::$registered)->filter(function ($notification) {
            return $notification::configurable();
        })->map(function ($notification) use ($notifiable) {
            return array_merge([
                'key' => $notification::key(),
                'name' => $notification::name(),
                'description' => $notification::description(),

                'channels' => $channels = collect($notification::availableChannels())
                    ->reject(fn ($channel) => $channel === 'broadcast')->values(),

            ], is_null($notifiable) ? [] : ['availability' => array_merge(
                $channels->mapWithKeys(fn ($channel) => [$channel => true])->all(),
                $notifiable->getNotificationPreference($notification::key())
            )]);
        })->values()->all();
    }
}
