<?php
 

namespace Modules\Core\App\Common\Placeholders;

use Closure;
use JsonSerializable;
use Modules\Core\App\Support\Makeable;

/**
 * @property-read mixed $value
 */
abstract class Placeholder implements JsonSerializable
{
    use Makeable;

    /**
     * Indicates the starting interpolation.
     */
    public string $interpolationStart = '{{';

    /**
     * Indicates the ending interpolation.
     */
    public string $interpolationEnd = '}}';

    /**
     * The placeholder description.
     */
    public ?string $description = null;

    /**
     * Custom value callback.
     *
     * @var null|callable
     */
    public $valueCallback;

    /**
     * Indicates whether the placeholder may contain new lines.
     */
    public bool $newlineable = false;

    /**
     * Initialize new Placeholder instance.
     *
     * @param  \Closure|mixed  $value
     */
    public function __construct(public string $tag, $value = null)
    {
        if ($value) {
            $this->value($value);
        }
    }

    /**
     * Format the placeholder.
     *
     * @return string
     */
    abstract public function format(?string $contentType = null);

    /**
     * Change the placeholder starting interpolation
     */
    public function withStartInterpolation(string $value): static
    {
        $this->interpolationStart = $value;

        return $this;
    }

    /**
     * Change the placeholder ending interpolation
     */
    public function withEndInterpolation(string $value): static
    {
        $this->interpolationEnd = $value;

        return $this;
    }

    /**
     * Set the placeholder value
     *
     * @param  mixed  $value
     */
    public function value($value): static
    {
        if ($value instanceof Closure) {
            $this->valueCallback = $value;
        } else {
            $this->value = $value;
        }

        return $this;
    }

    /**
     * Set placeholder tag
     */
    public function tag(string $tag): static
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Prefix the placeholder tag with the given prefix
     */
    public function prefixTag(string $prefix): static
    {
        return $this->tag($prefix.$this->tag);
    }

    /**
     * Set placeholder description
     */
    public function description(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Serialize the placeholder for the front end
     */
    public function jsonSerialize(): array
    {
        return [
            'tag' => $this->tag,
            'description' => $this->description,
            'interpolation_start' => $this->interpolationStart,
            'interpolation_end' => $this->interpolationEnd,
            'newlineable' => $this->newlineable,
        ];
    }

    /**
     * Dynamically access a property for the value
     *
     * E.q. can be used with provided callback on value or __constructor
     * Helpful when the data is not yet defined in the Mailable to prevent
     * throwing errors when fetching the placeholders tags for the front end
     *
     * In this case, before this code is executed, value will be null and we
     * will use the callback to get the value
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        if ($key === 'value' && $this->valueCallback) {
            return call_user_func($this->valueCallback);
        }
    }
}
