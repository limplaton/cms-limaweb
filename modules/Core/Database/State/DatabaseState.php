<?php
 

namespace Modules\Core\Database\State;

use Illuminate\Database\Eloquent\Model;

class DatabaseState
{
    protected static array $seeders = [];

    public static function register(string|array $class): void
    {
        static::$seeders = array_unique(array_merge(static::$seeders, (array) $class));
    }

    public static function seed(): void
    {
        collect(static::$seeders)->map(fn (string $class) => new $class)->each(function (object $instance) {
            Model::unguarded(function () use ($instance) {
                $instance->__invoke();
            });
        });
    }
}
