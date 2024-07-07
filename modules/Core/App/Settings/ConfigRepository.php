<?php
 

namespace Modules\Core\App\Settings;

use Illuminate\Config\Repository;

class ConfigRepository extends Repository
{
    /**
     * Get the specified configuration value.
     *
     * @param  array|string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (is_array($key)) {
            return $this->getMany($key);
        }

        $value = parent::get($key, $default);

        if (is_array($value)) {
            return $this->recursiveOverrides([$key => $value])[$key] ?? $default;
        } elseif (ConfigOverrides::shouldOverride($key)) {
            return ConfigOverrides::getSettingFor($key, $default);
        }

        return $value;
    }

    /**
     * Get many configuration values.
     *
     * @param  array  $keys
     * @return array
     */
    public function getMany($keys)
    {
        $config = [];

        foreach ($keys as $key => $default) {
            if (is_numeric($key)) {
                $config[$default] = $this->get($default);
            } else {
                $config[$key] = $this->get($key, $default);
            }
        }

        return $config;
    }

    /**
     * Get all of the configuration items for the application.
     *
     * @return array
     */
    public function all()
    {
        // Do not cache with overriden values, override only on runtime.
        if (str_starts_with(
            basename(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[0]['file']),
            'ConfigCacheCommand')
        ) {
            return $this->items;
        }

        return $this->recursiveOverrides($this->items);
    }

    /**
     * Recursive override array items.
     */
    protected function recursiveOverrides(array $items, string $prefix = ''): array
    {
        $result = [];

        foreach ($items as $key => $value) {
            $fullKey = $prefix ? $prefix.'.'.$key : $key;

            if (is_array($value)) {
                $value = $this->recursiveOverrides($value, $fullKey);
            }

            if (ConfigOverrides::shouldOverride($fullKey)) {
                $value = ConfigOverrides::getSettingFor($fullKey);
            }

            $result[$key] = $value;
        }

        return $result;
    }
}
