<?php
 

namespace Modules\Core\App\Common\Media;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use Modules\Core\App\Models\Media;
use Modules\Core\App\Models\Model;
use Plank\Mediable\Mediable;

/** @mixin \Modules\Core\App\Models\Model */
trait HasMedia
{
    use Mediable;

    /**
     * Boot HasMedia trait
     */
    protected static function bootHasMedia(): void
    {
        static::created(function (Model $model) {
            static::processMediaViaTextAttributes($model);
        });

        static::updated(function (Model $model) {
            static::processMediaViaTextAttributes($model);
        });

        static::deleting(function (Model $model) {
            if ($model->isReallyDeleting()) {
                $model->purgeMedia();
            }
        });
    }

    /**
     * Relationship for all the media via attributes.
     */
    public function mediaFromAttributes(): MorphToMany
    {
        return $this->media()->wherePivot('tag', $this->getAttributeableMediaTag());
    }

    /**
     * Get the attributes that may contain media and pending media.
     */
    public function textAttributesWithMedia(): string|array
    {
        return [];
    }

    /**
     * Get the model directly attached media tags.
     */
    public function getMediaTags(): array
    {
        return ['direct'];
    }

    /**
     * Get the tag that should be used when ataching media from editor/attributes.
     */
    public function getAttributeableMediaTag(): string
    {
        return 'editor';
    }

    /**
     * Get the model directly attached media directory.
     */
    public function getMediaDirectory(): string
    {
        $folder = Str::kebab(class_basename(get_called_class()));

        return config('core.media.directory').DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR.$this->id;
    }

    /**
     * Attach media from attribute.
     *
     * @param  string|int|Media|\Illuminate\Database\Eloquent\Collection  $media
     */
    public function attachAttributeableMedia($media): void
    {
        $this->attachMedia($media, $this->getAttributeableMediaTag());
    }

    /**
     * Detach media from attribute.
     *
     * @param  string|int|Media|\Illuminate\Database\Eloquent\Collection  $media
     */
    public function detachAttributeableMedia($media): void
    {
        $this->detachMedia($media, $this->getAttributeableMediaTag());
    }

    /**
     * Override only, we have our own logic regarding deletition.
     */
    protected function handleMediableDeletion(): void
    {
        //
    }

    /**
     * Purge the model media.
     */
    public function purgeMedia(): void
    {
        // First we will delete the directly attached media.
        $this->media()
            ->wherePivot('tag', '!=', $this->getAttributeableMediaTag())
            ->get()
            ->each(function (Media $media) {
                $media->delete();
            });

        // Next, we'll remove the linked media from attributes. Sometimes, the same media might be used in different places,
        // like in both activity notes and a note. If this happens, and a user decides to delete a note, we'll only
        // detach the media from that specific note without deleting the media entirely.
        // This way, if the media is also used in an activity created as a follow-up task, it won't be removed from there.
        $this->mediaFromAttributes()
            ->get()
            ->each(function (Media $media) {
                if ($media->totalModels() <= 1) {
                    $media->delete();
                } else {
                    $this->detachAttributeableMedia($media);
                }
            });
    }

    /**
     * Process the media embedded via text attributes.
     */
    protected static function processMediaViaTextAttributes(Model $model): void
    {
        static::createAttributesMediaProcessor()->process(
            $model->textAttributesWithMedia(),
            $model
        );
    }

    /**
     * Create media processor
     */
    protected static function createAttributesMediaProcessor(): AttributeableMediaProcessor
    {
        return new AttributeableMediaProcessor(
            Media::attributeMediableDirectory()
        );
    }
}
