<?php
 

namespace Modules\Core\App\Support;

trait InteractsWithEnums
{
    /**
     * Find enum by given name.
     */
    public static function find(string $name): ?static
    {
        return array_values(array_filter(static::cases(), function ($status) use ($name) {
            return $status->name == $name;
        }))[0] ?? null;
    }

    /**
     * Get a random enum instance.
     */
    public static function random(): self
    {
        return static::find(static::names()[array_rand(static::names())]);
    }

    /**
     * Get all the enum names.
     */
    public static function names(): array
    {
        return array_column(static::cases(), 'name');
    }
}
