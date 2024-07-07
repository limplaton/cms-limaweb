<?php
 

namespace Modules\Core\App\Facades;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use Modules\Core\App\Contracts\OAuth\StateStorage;

/**
 * @see \Modules\Core\App\Contracts\OAuth\StateStorage
 */
class OAuthState extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return StateStorage::class;
    }

    /**
     * Validate the returned state from OAuth
     *
     * @param  string  $current
     * @return bool
     */
    public static function validate($current)
    {
        return ! (empty($current)
                || (static::has() && ! static::matches($current)));
    }

    /**
     * Check whether provided state matches with
     *
     * the one in storage
     *
     * @param  string  $value
     * @return bool
     */
    public static function matches($value)
    {
        return $value === static::get();
    }

    /**
     * Create a custom OAuth state with parameters included
     *
     * @return string
     */
    public static function putWithParameters($parameters)
    {
        $state = base64_encode(json_encode($parameters));

        static::put($state);

        return $state;
    }

    /**
     * Get previously passsed paremeter from state
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function getParameter($key, $default = null)
    {
        $decoded = base64_decode(static::get());

        // State not valid for params
        if (! Str::isJson($decoded)) {
            return $default;
        }

        $params = json_decode($decoded);

        return $params->{$key} ?? $default;
    }
}
