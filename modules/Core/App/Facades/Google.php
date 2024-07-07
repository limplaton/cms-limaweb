<?php
 

namespace Modules\Core\App\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Core\App\Common\Google\Client;

/**
 * @method static static connectUsing(string|\Modules\Core\App\Common\OAuth\AccessTokenProvider)
 * @method static \Modules\Core\App\Common\Google\Services\Message message()
 * @method static \Modules\Core\App\Common\Google\Services\Labels labels()
 * @method static \Modules\Core\App\Common\Google\Services\History history()
 * @method static \Modules\Core\App\Common\Google\Services\Calendar calendar()
 * @method static void revokeToken(?string $accessToken = null)
 * @method static \Google\Client getClient()
 *
 * @see \Modules\Core\App\Common\Google\Client
 */
class Google extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
