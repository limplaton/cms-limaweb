<?php
 

namespace Modules\Core\App\Menu;

use JsonSerializable;

abstract class Metric implements JsonSerializable
{
    /**
     * Get the metric name.
     */
    abstract public function name(): string;

    /**
     * Get the metric count.
     */
    abstract public function count(): int;

    /**
     * Get the background color variant when the metric count is bigger then zero
     */
    abstract public function backgroundColorVariant(): string;

    /**
     * Get the front-end route that the highly will redirect to.
     */
    abstract public function route(): array|string;

    /**
     * Prepare the class for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return [
            'count' => $this->count(),
            'name' => $this->name(),
            'route' => $this->route(),
            'backgroundColorVariant' => $this->backgroundColorVariant(),
        ];
    }
}
