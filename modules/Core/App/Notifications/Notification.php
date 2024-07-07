<?php
 

namespace Modules\Core\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Str;
use Modules\Core\App\Contracts\HasNotificationsSettings;
use Modules\Core\App\Facades\Notifications;

class Notification extends BaseNotification
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     */
    public function via(HasNotificationsSettings $notifiable): array
    {
        $settings = $notifiable->getNotificationPreference(static::key());
        $channels = static::availableChannels();

        // All channels are enabled by default
        if (count($settings) === 0) {
            return $channels;
        }

        // Filter the channels the user specifically turned off
        $except = array_keys(array_filter(
            $settings, fn (bool $notify) => $notify === false
        ));

        return array_values(array_diff($channels, $except));
    }

    /**
     * Determine if the notification should be sent.
     */
    public function shouldSend(object $notifiable, string $channel): bool
    {
        if (Notifications::disabled()) {
            return false;
        }

        if (! $notifiable instanceof HasNotificationsSettings) {
            return true;
        }

        // When the user turned off all notifications, only the "broadcast" will be available,
        // we don't need to send the notification as the "broadcast" channel won't have a notification to broadcast.
        return ! ($channel === 'broadcast' && count($this->via($notifiable)) === 1);
    }

    /**
     * Provide the notification available delivery channels.
     */
    public static function availableChannels(): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the notification unique key identifier.
     */
    public static function key(): string
    {
        return Str::snake(class_basename(get_called_class()), '-');
    }

    /**
     * Get the displayable name of the notification.
     */
    public static function name(): string
    {
        return Str::title(Str::snake(class_basename(get_called_class()), ' '));
    }

    /**
     * Get the notification description.
     */
    public static function description(): ?string
    {
        return null;
    }

    /**
     * Define whether the notification is user-configurable.
     */
    public static function configurable(): bool
    {
        return true;
    }
}
