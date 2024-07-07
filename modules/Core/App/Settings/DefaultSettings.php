<?php
 

namespace Modules\Core\App\Settings;

class DefaultSettings
{
    /**
     * All of the registered default settings.
     */
    protected static array $defaults = [
        '_server_ip' => null,
        '_server_hostname' => null,
        '_installed_date' => null,
        '_last_updated_date' => null,
        '_db_driver_version' => null,
        '_db_driver' => null,
        '_app_url' => null,
        '_version' => null,
        'purchase_key' => null,
    ];

    /**
     * Get default setting(s).
     *
     * @param  string|null  $key
     * @return array|string|null
     */
    public static function get($key = null)
    {
        if ($key) {
            if (! array_key_exists($key, static::$defaults)) {
                return null;
            }

            if (! is_array(static::$defaults[$key])) {
                return static::$defaults[$key];
            }

            return static::$defaults[$key]['value'];
        }

        return collect(static::$defaults)->mapWithKeys(
            fn ($data, $key) => [$key => is_array($data) ? $data['value'] : $data]
        )->all();
    }

    /**
     * Get the settings that are required,
     */
    public static function getRequired(): array
    {
        return collect(static::$defaults)->filter(
            fn ($data) => $data['required'] ?? false === true
        )->keys()->all();
    }

    /**
     * Add new default setting.
     */
    public static function add(string|array $key, mixed $value = null, $required = false): void
    {
        if (is_array($key)) {
            foreach ($key as $k) {
                static::add($k, $value, $required);
            }

            return;
        }

        static::$defaults[$key] = ['required' => $required, 'value' => $value];
    }

    /**
     * Add new required default setting.
     */
    public static function addRequired(string $key, mixed $value = null): void
    {
        static::add($key, $value, true);
    }

    /**
     * Check whether the given settings key is required.
     */
    public static function isRequired(string $key): bool
    {
        return in_array($key, static::getRequired());
    }
}
