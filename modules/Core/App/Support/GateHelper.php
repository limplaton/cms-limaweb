<?php
 

namespace Modules\Core\App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use ReflectionClass;
use ReflectionMethod;

class GateHelper
{
    public static array $reflections = [];

    public static $checked = [];

    public static $policies = [];

    public static function authorizations(Model $model, array $exclude = []): ?array
    {
        $cacheKey = $model::class.$model->getKey();

        if (isset(static::$checked[$cacheKey])) {
            return static::$checked[$cacheKey];
        }

        if (! array_key_exists($model::class, static::$policies)) {
            static::$policies[$model::class] = Gate::getPolicyFor($model::class);
        }

        if (! $policy = static::$policies[$model::class]) {
            return null;
        }

        $abilities = static::$reflections[$policy::class] ??= static::getAbilities($policy, $exclude);

        if (array_key_exists($cacheKey, static::$checked)) {
            return static::$checked[$cacheKey];
        }

        $checks = [];

        foreach ($abilities as $ability) {
            $checks[$ability] = Gate::allows($ability, $model);
        }

        return static::$checked[$cacheKey] = $checks;
    }

    protected static function getAbilities($policy, $exclude): array
    {
        return collect((new ReflectionClass($policy))
            ->getMethods(ReflectionMethod::IS_PUBLIC))
            ->reject(function ($method) use ($exclude) {
                return in_array($method->name, array_merge($exclude, ['denyAsNotFound', 'denyWithStatus', 'before']));
            })
            ->map(fn ($method) => $method->name)
            ->all();
    }

    public static function flushCache(): void
    {
        static::$reflections = [];
        static::$checked = [];
        static::$policies = [];
    }
}
