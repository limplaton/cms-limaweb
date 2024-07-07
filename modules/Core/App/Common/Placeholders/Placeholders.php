<?php
 

namespace Modules\Core\App\Common\Placeholders;

use Illuminate\Support\Arr;
use JsonSerializable;
use KubAT\PhpSimple\HtmlDomParser;
use Mustache_Engine;
use Mustache_Exception_SyntaxException;

class Placeholders implements JsonSerializable
{
    /**
     * Parsed placeholders cache.
     */
    protected array $parsed = [];

    /**
     * Placeholder selector.
     */
    const PLACEHOLDER_SELECTOR = '._placeholder';

    /**
     * Create new Collection instance.
     */
    public function __construct(protected array $placeholders)
    {
    }

    /**
     * Get all of the collection placeholders.
     */
    public function all(): array
    {
        return $this->placeholders;
    }

    /**
     * Remove placeholder from the collection.
     */
    public function forget(string|array $tagName): static
    {
        $this->placeholders = collect($this->placeholders)->reject(
            fn ($placeholder) => in_array($placeholder->tag, (array) $tagName)
        )->values()->all();

        $this->parsed = [];

        return $this;
    }

    /**
     * Add new placeholder to the collection.
     */
    public function push(Placeholder|array $placeholders): static
    {
        $this->placeholders = array_merge(
            $this->placeholders,
            is_array($placeholders) ? $placeholders : func_get_args()
        );

        $this->parsed = [];

        return $this;
    }

    /**
     * Parse all the placeholders with their formatted values.
     */
    public function parse(?string $contentType = 'html'): array
    {
        $cacheKey = $contentType ?: 'general';

        return $this->parsed[$cacheKey] ??= $this->performFormatting($contentType);
    }

    /**
     * Parse the resource placeholders.
     */
    public function parseWhenViaInputFields(?string $content): ?string
    {
        if ($content === '' || is_null($content)) {
            return $content;
        }

        $placeholders = Arr::dot($this->parse());

        $dom = HtmlDomParser::str_get_html($content);
        $domPlaceholders = $dom->find(static::PLACEHOLDER_SELECTOR);

        foreach ($domPlaceholders as $element) {
            foreach ($placeholders as $tag => $value) {
                $htmlTag = $element->getAttribute('data-tag');

                // // For previous versions (1.1.9 and below), where the tags were not prefixed with the resource name
                if ($element->hasAttribute('data-group')) {
                    $element->value = 'INVALID PLACEHOLDER, DELETE AND RE-ADD.';
                } else {
                    if (empty(trim($element->value)) && $htmlTag == $tag) {
                        if ($element->tag === 'textarea') {
                            $element->innertext = $value;
                        } else {
                            $element->value = $value;
                        }

                        if (! empty($value)) {
                            $element->setAttribute('data-autofilled', true);
                        }
                    }
                }
            }
        }

        return $dom->save();
    }

    /**
     * Clean up the given content from placeholders via input fields.
     */
    public static function cleanup(?string $content): ?string
    {
        if ($content === '' || is_null($content)) {
            return $content;
        }

        $dom = HtmlDomParser::str_get_html($content, true, true, DEFAULT_TARGET_CHARSET, false);

        foreach ($dom->find(static::PLACEHOLDER_SELECTOR) as $element) {
            if ($element->tag === 'textarea') {
                $element->outertext = ! empty($element) ? nl2br(trim($element->innertext)) : '';
            } else {
                $element->outertext = ! empty($element) ? trim($element->value) : '';
            }
        }

        return $dom->save();
    }

    /**
     * Replace the placeholders to the given template.
     *
     * @param  string  $template
     * @return string
     */
    public function render($template)
    {
        if ($template === '' || is_null($template)) {
            return $template;
        }

        try {
            return (new Mustache_Engine)->render($template, $this->parse());
        } catch (Mustache_Exception_SyntaxException) {
            return $template;
        }
    }

    /**
     * Perform formatting on the placeholders
     */
    protected function performFormatting(?string $contentType): array
    {
        return collect($this->placeholders)->mapWithKeys(
            fn (Placeholder $placeholder) => [$placeholder->tag => $placeholder->format($contentType)]
        )->undot()->all();
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return $this->placeholders;
    }
}
