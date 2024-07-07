<?php
 

namespace Modules\Core\App\Fields;

use Illuminate\Cache\Repository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Modules\Core\App\Models\CustomField;
use Modules\Core\App\Models\CustomFieldOption;

class CustomFieldFileCache
{
    protected static string $cacheKey = 'custom_fields';

    public static function get(): CustomFieldCollection
    {
        if (! static::cached()) {
            static::put();
        }

        return static::store()->get(static::$cacheKey, function () {
            return static::toCollection(static::retrieve()->toArray());
        });
    }

    public static function flush(): bool
    {
        return static::store()->forget(static::$cacheKey);
    }

    public static function refresh(): void
    {
        static::put();
    }

    public static function put(): void
    {
        $fields = static::retrieve();

        static::store()->forever(static::$cacheKey, $fields);
    }

    public static function cached(): bool
    {
        return static::store()->has(static::$cacheKey);
    }

    public static function toCollection(array $fields): CustomFieldCollection
    {
        $model = new CustomField;

        return $model->newCollection($fields)
            ->map(function (array $field) use ($model) {
                $options = static::optionsToCollection(Arr::pull($field, 'options'));

                return $model->newInstance($field, true)
                    ->forceFill(['id' => $field['id']])
                    ->setRelation('options', $options);
            });
    }

    protected static function optionsToCollection(array $options): Collection
    {
        $model = new CustomFieldOption;

        return $model->newCollection($options)
            ->map(function (array $option) use ($model) {
                return $model->newInstance($option, true)->forceFill(['id' => $option['id']]);
            });
    }

    protected static function store(): Repository
    {
        return Cache::driver('file');
    }

    protected static function retrieve(): CustomFieldCollection
    {
        return CustomField::with('options')->get();
    }
}
