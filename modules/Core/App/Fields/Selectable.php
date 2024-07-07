<?php
 

namespace Modules\Core\App\Fields;

trait Selectable
{
    /**
     * Display a view icon on selected option (multiple only)
     * The route is based of the option "path" key.
     */
    public function displayOptionViewActionFromPath(): static
    {
        $this->withMeta(['displayOptionViewActionFromPath' => true]);

        return $this;
    }

    /**
     * Indicates that the "X" select field is hidden.
     */
    public function withoutClearAction(): static
    {
        $this->withMeta(['attributes' => ['clearable' => false]]);

        return $this;
    }

    /**
     * Check whether the field is async.
     */
    public function isAsync(): bool
    {
        return isset($this->meta['asyncUrl']);
    }

    /**
     * Set async URL for searching.
     */
    public function async(string $asyncUrl): static
    {
        $this->withMeta([
            'asyncUrl' => $asyncUrl,
            'attributes' => ['placeholder' => __('core::app.type_to_search')],
        ]);

        return $this;
    }

    /**
     * Set the URL to lazy load options when the field is first opened.
     */
    public function lazyLoad(string $url, array $params = []): static
    {
        $this->withMeta(['lazyLoad' => [
            'url' => $url,
            'params' => $params,
        ]]);

        return $this;
    }
}
