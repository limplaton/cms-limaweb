<?php
 

namespace Modules\Core\App\Support;

trait HasMeta
{
    /**
     * Additional field meta
     */
    public array $meta = [];

    /**
     * Get the element meta
     */
    public function meta(): array
    {
        return $this->meta;
    }

    /**
     * Add element meta
     */
    public function withMeta(array $attributes): static
    {
        $this->meta = array_merge_recursive($this->meta, $attributes);

        return $this;
    }
}
