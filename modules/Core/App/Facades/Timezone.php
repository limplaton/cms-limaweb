<?php
 

namespace Modules\Core\App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string convertFromUTC(string $timestamp, ?string $timezone = null, string $format = 'Y-m-d H:i:s')
 * @method static string convertToUTC(string $timestamp, ?string $timezone = null, string $format = 'Y-m-d H:i:s')
 * @method static string fromUTC(string $timestamp, ?string $timezone = null, string $format = 'Y-m-d H:i:s')
 * @method static string toUTC(string $timestamp, ?string $timezone = null, string $format = 'Y-m-d H:i:s')
 * @method static string current(?\Modules\Core\App\Contracts\Localizeable $user = null)
 * @method static array all()
 * @method static array toArray()
 *
 * @see \Modules\Core\App\Timezone
 */
class Timezone extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'timezone';
    }
}
