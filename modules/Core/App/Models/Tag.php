<?php
 

namespace Modules\Core\App\Models;

use ArrayAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as DatabaseCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;
use Modules\Core\App\Concerns\HasDisplayOrder;
use Modules\Core\Database\Factories\TagFactory;

class Tag extends CacheModel
{
    use HasDisplayOrder, HasFactory;

    public $guarded = [];

    public function scopeWithType(Builder $query, ?string $type = null): Builder
    {
        if (is_null($type)) {
            return $query;
        }

        return $query->where('type', $type);
    }

    public function scopeContaining(Builder $query, string $name): Builder
    {
        return $query->whereRaw('lower('.$this->getQuery()->getGrammar()->wrap('name').') like ?', ['%'.mb_strtolower($name).'%']);
    }

    public static function findOrCreate(
        string|array|ArrayAccess $values,
        ?string $type = null,
    ): Collection|Tag|static {
        $tags = collect($values)->map(function ($value) use ($type) {
            if ($value instanceof self) {
                return $value;
            }

            return static::findOrCreateFromString($value, $type);
        });

        return is_string($values) ? $tags->first() : $tags;
    }

    public static function getWithType(string $type): DatabaseCollection
    {
        return static::withType($type)->get();
    }

    public static function findFromString(string $name, ?string $type = null)
    {
        return static::query()
            ->where('type', $type)
            ->where('name', $name)
            ->first();
    }

    public static function findFromStringOfAnyType(string $name)
    {
        return static::query()
            ->where('name', $name)
            ->get();
    }

    protected static function findOrCreateFromString(string $name, ?string $type = null)
    {
        $tag = static::findFromString($name, $type);

        if (! $tag) {
            $tag = static::create([
                'name' => $name,
                'type' => $type,
                'display_order' => 1000,
            ]);
        }

        return $tag;
    }

    public static function getTypes(): Collection
    {
        return static::groupBy('type')->pluck('type');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): TagFactory
    {
        return new TagFactory;
    }
}
