<?php
 

namespace Modules\Core\App\Support;

use Illuminate\Support\Str;
use Nwidart\Modules\Facades\Module;
use Symfony\Component\Finder\Finder;

class ModelFinder
{
    public static ?array $models = null;

    public static function find(): array
    {
        if (! is_null(static::$models)) {
            return static::$models;
        }

        return static::$models = collect(static::finder())->map(function ($model) {
            if (str_contains($model, config('modules.paths.modules'))) {
                return config('modules.namespace').'\\'.str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    Str::after($model->getRealPath(), realpath(config('modules.paths.modules')).DIRECTORY_SEPARATOR)
                );
            }

            return app()->getNamespace().str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($model->getRealPath(), realpath(app_path()).DIRECTORY_SEPARATOR)
            );
        })->all();
    }

    protected static function finder(): Finder
    {
        return (new Finder)->in(static::paths())->files()->name('*.php');
    }

    protected static function paths(): array
    {
        $paths = array_filter(array_values(array_map(function ($module) {
            $path = module_path($module->getLowerName(), config('modules.paths.generator.model.path'));

            return is_dir($path) ? $path : null;
        }, Module::allEnabled())));

        $paths[] = app_path('Models');

        return $paths;
    }
}
