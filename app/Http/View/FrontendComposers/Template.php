<?php
 

namespace App\Http\View\FrontendComposers;

use JsonSerializable;

class Template implements JsonSerializable
{
    public ?Component $detailComponent = null;

    /**
     * Set the view component instance.
     */
    public function detailComponent(Component $component): static
    {
        $this->detailComponent = $component;

        return $this;
    }

    /**
     * Prepare the template for front-end
     */
    public function jsonSerialize(): array
    {
        return [
            'detail' => $this->detailComponent,
        ];
    }
}
