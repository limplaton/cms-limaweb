<?php
 

namespace Modules\Core\App\Settings;

use Modules\Core\App\Settings\Contracts\Manager;

class ConfigOverrides
{
    /**
     * Config overrides.
     */
    protected static array $overrides = [];

    /**
     * Add settings keys that override config keys.
     */
    public static function add(array $keys): void
    {
        foreach ($keys as $configKey => $settingKey) {
            static::$overrides[$configKey] = $settingKey;
        }
    }

    /**
     * Get the setting key for the given config key.
     */
    public static function getFor(string $key): mixed
    {
        return static::$overrides[$key];
    }

    /**
     * Check if there is an override from settings for the given config key.
     */
    public static function hasFor(string $key): bool
    {
        return array_key_exists($key, static::$overrides);
    }

    /**
     * Check if the given config key should be overriden.
     */
    public static function shouldOverride(string $key): bool
    {
        return static::hasFor($key) && static::hasSettingFor($key);
    }

    /**
     * Check if the given override config key has setting available in storage.
     */
    public static function hasSettingFor(string $key): bool
    {
        $settingsKey = static::getFor($key);
        $settings = app(Manager::class);

        return $settings->has($settingsKey);
    }

    /**
     * Get setting override value for the given config key.
     */
    public static function getSettingFor(string $key, mixed $default = null)
    {
        $settingsKey = static::getFor($key);
        $settings = app(Manager::class);

        return $settings->get($settingsKey, $default);
    }
}
