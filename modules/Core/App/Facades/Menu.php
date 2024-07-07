<?php
 

namespace Modules\Core\App\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Core\App\Menu\MenuManager;

/**
 * @method static static register(\Modules\Core\App\Menu\MenuItem|array<\Modules\Core\App\Menu\MenuItem> $items)
 * @method static static registerItem(\Modules\Core\App\Menu\MenuItem $item)
 * @method static \Illuminate\Support\Collection<int, \Modules\Core\App\Menu\MenuItem> get()
 * @method static static metric(\Modules\Core\App\Menu\Metric|array<\Modules\Core\App\Menu\Metric> $metric)
 * @method static \Modules\Core\App\Menu\Metric[] metrics()
 * @method static static clear()
 *
 * @see \Modules\Core\App\Menu\MenuManager
 */
class Menu extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return MenuManager::class;
    }
}
