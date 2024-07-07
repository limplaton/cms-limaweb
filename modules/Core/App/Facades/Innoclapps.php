<?php
 

namespace Modules\Core\App\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Core\App\Application;

/**
 * @method static void boot()
 * @method static void booting(callable $callback)
 * @method static void booted(callable $callback)
 * @method static string version()
 * @method static string systemName()
 * @method static array locales()
 * @method static array allowedUploadExtensions()
 * @method static \Modules\Core\App\Resource\Resource resourceByName(string $name)
 * @method static \Modules\Core\App\Resource\Resource resourceByModel(string|\Modules\Core\App\Models\Model $model)
 *
 * @see \Modules\Core\App\Application
 * */
class Innoclapps extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return Application::class;
    }
}
