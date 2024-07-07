<?php
 

namespace Modules\Core\App\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Core\App\Card\CardsManager;

/**
 * @method static static register(string $resourceName, mixed $provider)
 * @method static \Illuminate\Support\Collection resolve(string $resourceName)
 * @method static \Illuminate\Support\Collection forResource(string $resourceName)
 * @method static \Illuminate\Support\Collection resolveForDashboard()
 * @method static \Illuminate\Support\Collection registered()
 *
 * @see \Modules\Core\App\Card\CardsManager
 */
class Cards extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return CardsManager::class;
    }
}
