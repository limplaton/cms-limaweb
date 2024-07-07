<?php
 

namespace Modules\Core\App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static ?string getSiteKey()
 * @method static static setSiteKey(?string $key)
 * @method static ?string getSecretKey()
 * @method static static setSecretKey(?string $key)
 * @method static array getSkippedIps()
 * @method static static setSkippedIps(array|string $ips)
 * @method static bool shouldShow(?string $ip = null)
 * @method static bool shouldSkip(?string $ip = null)
 * @method static bool configured()
 *
 * @see \Modules\Core\App\Support\ReCaptcha
 */
class ReCaptcha extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'recaptcha';
    }
}
