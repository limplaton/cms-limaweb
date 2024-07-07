<?php
 

use Akaunting\Money\Currency;
use Akaunting\Money\Money;
use Illuminate\Support\Str;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Settings\Contracts\Manager as SettingsManager;

if (! function_exists('format_bytes')) {
    /**
     * Format the given bytes in a proper human readable format.
     *
     * @param  int|float  $bytes
     */
    function format_bytes($bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }
}

if (! function_exists('timezone')) {
    /**
     * Helper timezone function.
     *
     * @return \Modules\Core\App\Timezone
     */
    function timezone()
    {
        return app('timezone');
    }
}

if (! function_exists('tz')) {
    /**
     * Alias to timezone() function.
     *
     * @return \Modules\Core\App\Timezone
     */
    function tz()
    {
        return timezone();
    }
}

if (! function_exists('get_generated_lang')) {
    /**
     * Get the application generate language.
     */
    function get_generated_lang(?string $locale = null): object
    {
        $path = config('translator.json');

        if (! is_file($path)) {
            return [];
        }

        $content = json_decode(
            file_get_contents($path)
        );

        if (is_null($locale)) {
            return $content;
        }

        return tap(new stdClass, function ($localeClass) use ($content, $locale) {
            if (isset($content->{$locale})) {
                $localeClass->{$locale} = $content->{$locale};
            } else {
                foreach ([config('app.locale'), config('app.fallback_locale')] as $fallback) {
                    if (isset($content->{$fallback})) {
                        $localeClass->{$fallback} = $content->{$fallback};

                        break;
                    }
                }
            }
        });
    }
}

if (! function_exists('clone_prefix')) {
    /**
     * Add clone prefix to the given string.
     *
     * Can be used when cloning models, to generaet unique name/title etc...
     */
    function clone_prefix(string $to): string
    {
        $title = preg_replace('/\s-\sCopy\([a-zA-Z0-9]{6}+\)/', '', $to);

        return $title.' - Copy('.Str::random(6).')';
    }
}

if (! function_exists('privacy_url')) {
    /**
     * Application privacy policy url.
     */
    function privacy_url(): string
    {
        return url('/privacy-policy');
    }
}

if (! function_exists('settings')) {
    /**
     * Get the settings manager instance.
     *
     * @param  string|array|null  $driver
     * @param  bool  $save
     * @return mixed
     */
    function settings($driver = null, $save = true)
    {
        $manager = app(SettingsManager::class);

        if ($driver) {
            if (is_array($driver)) {
                return tap($manager->set($driver), fn ($instance) => $save && $instance->save());
            }

            if (in_array($driver, array_keys(config('settings.drivers')))) {
                return $manager->driver($driver);
            }

            return $manager->get($driver);
        }

        return $manager;
    }
}

if (! function_exists('clean')) {
    /**
     * Clean the given string.
     *
     * @param  string  $dirty
     * @param  mixed  $config
     * @return string
     */
    function clean($dirty, $config = null)
    {
        return app('purifier')->clean($dirty, $config);
    }
}

if (! function_exists('get_current_process_user')) {
    /**
     * Get the current PHP process user.
     *
     * The function returns the process user not the file owner user like get_current_user().
     */
    function get_current_process_user(): string
    {
        if (! function_exists('posix_getpwuid')) {
            return get_current_user();
        }

        return posix_getpwuid(posix_geteuid())['name'] ?? null;
    }
}

if (! function_exists('forgot_password_is_disabled')) {
    /**
     * Check if the forgot password auth feature is disabled.
     */
    function forgot_password_is_disabled(): bool
    {
        return settings('disable_password_forgot') === true;
    }
}

if (! function_exists('to_money')) {
    /**
     * Create new Money instance.
     *
     * @param  string|int|float  $value
     * @return \Akaunting\Money\Money
     */
    function to_money($value, string|Currency|null $currency = null)
    {
        return Innoclapps::currency($currency)->toMoney($value);
    }
}

if (! function_exists('set_alert')) {
    /**
     * Set web alert.
     *
     * @param  string|null  $message
     * @param  string  $variant
     * @return void
     */
    function set_alert($message, $variant)
    {
        session()->flash($variant, $message);
    }
}

if (! function_exists('get_current_alert')) {
    /**
     * Get the alert in session.
     */
    function get_current_alert(): ?array
    {
        foreach (['primary', 'success', 'info', 'warning', 'danger'] as $type) {
            if ($message = session()->get($type)) {
                return ['variant' => $type, 'message' => $message];
            }
        }

        return null;
    }
}
