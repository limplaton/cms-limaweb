<?php
 

namespace Modules\Core\App\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Core\App\Settings\SettingsMenu as SettingsMenuManager;

/**
 * @method static void register(\Modules\Core\App\Settings\SettingsMenuItem $item, string $id)
 * @method static void add(string $id, \Modules\Core\App\Settings\SettingsMenuItem $item)
 * @method static ?\Modules\Core\App\Settings\SettingsMenuItem find(string $id)
 * @method static array<int,\Modules\Core\App\Settings\SettingsMenuItem> all()
 *
 * @see \Modules\Core\App\Settings\SettingsMenu
 */
class SettingsMenu extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return SettingsMenuManager::class;
    }
}
