<?php
 

namespace Modules\Core\App\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Core\App\Tools as ToolsManager;

/**
 * @method static static register(string $key, callable $callback, ?string $description = null)
 * @method static mixed execute(string $name)
 * @method static array all()
 * @method static bool has(string $tool)
 *
 * @see \Modules\Core\App\Tools
 */
class Tools extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return ToolsManager::class;
    }
}
