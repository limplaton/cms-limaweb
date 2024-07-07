<?php
 

namespace Modules\Core\App\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Core\App\Permissions\PermissionsManager;

/**
 * @method static void group(string|array $group, \Closure $callback)
 * @method static array groups()
 * @method static void view(string $view, array $data)
 * @method static void createMissing()
 * @method static array all()
 * @method static array labeled()
 * @method static void register(\Closure $callback)
 *
 * @see \Modules\Core\App\Support\PermissionsManager
 */
class Permissions extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return PermissionsManager::class;
    }
}
