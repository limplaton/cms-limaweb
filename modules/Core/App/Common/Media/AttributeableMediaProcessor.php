<?php
 

namespace Modules\Core\App\Common\Media;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use KubAT\PhpSimple\HtmlDomParser;
use Modules\Core\App\Models\Media;
use Modules\Core\App\Models\PendingMedia;
use Stringable;

class AttributeableMediaProcessor
{
    /**
     * A regex to match media URL's in string.
     */
    const MEDIA_REGEX = '/\/media\/([\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12})/m';

    /**
     * Initialize new AttributeableMediaProcessor instance.
     */
    public function __construct(protected string $mediaDir)
    {
    }

    /**
     * Process editor media via given model and attributes.
     */
    public function process(array|string $attributes, Model $model): void
    {
        foreach (Arr::wrap($attributes) as $attribute) {
            $this->processAttribute($attribute, $model);
        }
    }

    /**
     * Process the attribute for media.
     */
    public function processAttribute(string $attribute, Model $model): void
    {
        $value = $this->toString($model->{$attribute});

        $tokens = $this->getMediaTokensFromText($value);

        $this->convertPendingMediaToMedia($tokens, $model);

        // Check if it's update, if yes, get the removed media tokens and delete/detach them
        if (! $model->wasRecentlyCreated) {
            $this->handleMediaRemoval($value, $tokens, $model, $attribute);
        }

        // Finally, attach all of the media to the model
        $this->attachMediaToModel($model, $tokens);
    }

    /**
     * Handle any media removals from the attribute value.
     */
    protected function handleMediaRemoval($value, $tokens, Model $model, string $attribute)
    {
        $originalValue = $model->getOriginal($attribute);

        if ($value instanceof Stringable) {
            $originalValue = (string) $originalValue;
        }

        if ($deletedTokens = $this->getRemovedMedia($originalValue, $tokens)) {
            $model->media()
                ->byTokens($deletedTokens)
                ->get()
                ->each(function (Media $media) use ($model) {
                    if ($media->totalModels() <= 1) {
                        $media->delete();
                    } else {
                        $model->detachAttributeableMedia($media);
                    }
                });
        }
    }

    /**
     * Get the removed media from the editor content
     *
     * @param  string|\Stringable|array|null  $originalText
     * @param  string|\Stringable|array  $newText
     */
    public function getRemovedMedia($originalText, $newText): array
    {
        $newText = $this->toString($newText);
        $originalText = $this->toString($originalText);

        if (is_null($originalText)) {
            return [];
        }

        return array_diff(
            is_string($originalText) ? $this->getMediaTokensFromText($originalText) : $originalText,
            is_string($newText) ? $this->getMediaTokensFromText($newText) : $newText
        );
    }

    /**
     * Get the attribute current media tokens.
     *
     * @param  string|\Stringable  $text
     */
    public function getMediaTokensFromText($text): array
    {
        $text = $this->toString($text);

        return array_merge(
            $this->getMediaTokensFromImagesAndVideos($text),
            $this->getMediaTokensInlineBackgroundImages($text),
        );
    }

    /**
     * Attach all of the media to the model.
     */
    protected function attachMediaToModel(Model $model, array $tokens): void
    {
        $model->detachMediaTags($model->getAttributeableMediaTag());

        if (count($tokens)) {
            $model->attachAttributeableMedia(Media::byTokens($tokens)->get());
        }
    }

    /**
     * Convert any new pending media to media.
     */
    protected function convertPendingMediaToMedia(array $tokens): void
    {
        // Handle all pending medias and move them to the appropriate
        // directory and also delete the pending record from the pending table after move
        // From the current, we will get only the pending which are not yet processed
        PendingMedia::with('attachment')
            ->whereHas('attachment', fn (Builder $query) => $query->byTokens($tokens))
            ->get()
            ->each(function (PendingMedia $media) {
                $media->unmarkAsPending($this->mediaDir, Str::random(30));
            });
    }

    /**
     * Extract media tokens from images and videos.
     */
    protected function getMediaTokensFromImagesAndVideos(?string $text): array
    {
        $tokens = [];

        if (! $dom = HtmlDomParser::str_get_html($text)) {
            return $tokens;
        }

        // Process images and videos
        foreach ($dom->find('img,source') as $element) {
            if ($src = $element->getAttribute('src')) {
                if (preg_match(static::MEDIA_REGEX, $src, $matches)) {
                    $tokens[] = $matches[1];
                }
            }
        }

        return $tokens;
    }

    /**
     * Extract media tokens from inline background images.
     */
    protected function getMediaTokensInlineBackgroundImages(?string $text): array
    {
        $tokens = [];

        if (! $text) {
            return $tokens;
        }

        $bgImageMediaRegex = '/background\-image:(?: {1,}|)url(?: {1,}|)\([\'|"](.*)[\'|"]\)/';

        preg_match_all($bgImageMediaRegex, html_entity_decode($text, ENT_QUOTES), $bgImages, PREG_SET_ORDER, 0);

        foreach ($bgImages as $match) {
            if (preg_match(static::MEDIA_REGEX, $match[1], $matches)) {
                $tokens[] = $matches[1];
            }
        }

        return $tokens;
    }

    /**
     * Convert the given value to string if implements the \Stringable interface.
     */
    protected function toString(mixed $value): mixed
    {
        if ($value instanceof Stringable) {
            $value = (string) $value;
        }

        return $value;
    }
}
