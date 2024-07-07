<?php
 

namespace Modules\Core\App\Concerns;

use ArrayAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Models\Tag;

/** @mixin \Modules\Core\App\Models\Model */
trait HasTags
{
    protected static function bootHasTags(): void
    {
        static::deleted(function (Model $model) {
            if ($model->isReallyDeleting()) {
                $model->detachTags($model->tags()->get());
            }
        });
    }

    public function getTaggableMorphName(): string
    {
        return 'taggable';
    }

    public function getTaggableTableName(): string
    {
        return 'taggables';
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, $this->getTaggableMorphName());
    }

    public function scopeWithAllTags(
        Builder $query,
        string|array|ArrayAccess|Tag $tags,
        ?string $type = null,
    ): Builder {
        $tags = static::convertToTags($tags, $type);

        collect($tags)->each(function ($tag) use ($query) {
            $query->whereHas('tags', function (Builder $query) use ($tag) {
                $query->where('tags.id', $tag->id ?? 0);
            });
        });

        return $query;
    }

    public function scopeWithAnyTags(
        Builder $query,
        string|array|ArrayAccess|Tag $tags,
        ?string $type = null,
    ): Builder {
        $tags = static::convertToTags($tags, $type);

        return $query->whereHas('tags', function (Builder $query) use ($tags) {
            $tagIds = collect($tags)->pluck('id');

            $query->whereIn('tags.id', $tagIds);
        });
    }

    public function scopeWithoutTags(
        Builder $query,
        string|array|ArrayAccess|Tag $tags,
        ?string $type = null
    ): Builder {
        $tags = static::convertToTags($tags, $type);

        return $query->whereDoesntHave('tags', function (Builder $query) use ($tags) {
            $tagIds = collect($tags)->pluck('id');

            $query->whereIn('tags.id', $tagIds);
        });
    }

    public function scopeWithAllTagsOfAnyType(Builder $query, $tags): Builder
    {
        $tags = static::convertToTagsOfAnyType($tags);

        collect($tags)->each(function ($tag) use ($query) {
            $query->whereHas(
                'tags',
                fn (Builder $query) => $query->where('tags.id', $tag ? $tag->id : 0)
            );
        });

        return $query;
    }

    public function scopeWithAnyTagsOfAnyType(Builder $query, $tags): Builder
    {
        $tags = static::convertToTagsOfAnyType($tags);

        $tagIds = collect($tags)->pluck('id');

        return $query->whereHas(
            'tags',
            fn (Builder $query) => $query->whereIn('tags.id', $tagIds)
        );
    }

    public function tagsWithType(?string $type = null): Collection
    {
        return $this->tags->filter(fn (Tag $tag) => $tag->type === $type);
    }

    public function attachTags(array|ArrayAccess|Tag $tags, ?string $type = null): static
    {
        $tags = collect(Tag::findOrCreate($tags, $type));

        $this->tags()->syncWithoutDetaching($tags->pluck('id')->toArray());

        return $this;
    }

    public function attachTag(string|Tag $tag, ?string $type = null)
    {
        return $this->attachTags([$tag], $type);
    }

    public function detachTags(array|ArrayAccess $tags, ?string $type = null): static
    {
        $tags = static::convertToTags($tags, $type);

        collect($tags)->filter()->each(fn (Tag $tag) => $this->tags()->detach($tag));

        return $this;
    }

    public function detachTag(string|Tag $tag, ?string $type = null): static
    {
        return $this->detachTags([$tag], $type);
    }

    public function syncTags(string|array|ArrayAccess $tags): static
    {
        if (is_string($tags)) {
            $tags = Arr::wrap($tags);
        }

        $tags = collect(Tag::findOrCreate($tags));

        $this->tags()->sync($tags->pluck('id')->toArray());

        return $this;
    }

    public function syncTagsWithType(array|ArrayAccess $tags, ?string $type = null): static
    {
        $tags = collect(Tag::findOrCreate($tags, $type));

        $this->syncTagIds($tags->pluck('id')->toArray(), $type);

        return $this;
    }

    protected static function convertToTags($values, $type = null)
    {
        if ($values instanceof Tag) {
            $values = [$values];
        }

        return collect($values)->map(function ($value) use ($type) {
            if ($value instanceof Tag) {
                if (isset($type) && $value->type != $type) {
                    throw new InvalidArgumentException("Type was set to {$type} but tag is of type {$value->type}");
                }

                return $value;
            }

            return Tag::findFromString($value, $type);
        });
    }

    protected static function convertToTagsOfAnyType($values)
    {
        return collect($values)->map(function ($value) {
            if ($value instanceof Tag) {
                return $value;
            }

            return Tag::findFromStringOfAnyType($value);
        })->flatten();
    }

    protected function syncTagIds($ids, ?string $type = null, $detaching = true): void
    {
        $isUpdated = false;

        // Get a list of tag_ids for all current tags
        $current = $this->tags()
            ->newPivotStatement()
            ->where($this->getTaggableMorphName().'_id', $this->getKey())
            ->where($this->getTaggableMorphName().'_type', $this->getMorphClass())
            ->when($type !== null, function ($query) use ($type) {
                $tagModel = $this->tags()->getRelated();

                return $query->join(
                    $tagModel->getTable(),
                    $this->getTaggableTableName().'.tag_id',
                    '=',
                    $tagModel->getTable().'.'.$tagModel->getKeyName()
                )
                    ->where($tagModel->getTable().'.type', $type);
            })
            ->pluck('tag_id')
            ->all();

        // Compare to the list of ids given to find the tags to remove
        $detach = array_diff($current, $ids);
        if ($detaching && count($detach) > 0) {
            $this->tags()->detach($detach);
            $isUpdated = true;
        }

        // Attach any new ids
        $attach = array_unique(array_diff($ids, $current));
        if (count($attach) > 0) {
            collect($attach)->each(function ($id) {
                $this->tags()->attach($id, []);
            });
            $isUpdated = true;
        }

        // Once we have finished attaching or detaching the records, we will see if we
        // have done any attaching or detaching, and if we have we will touch these
        // relationships if they are configured to touch on any database updates.
        if ($isUpdated) {
            $this->tags()->touchIfTouching();
        }
    }
}
