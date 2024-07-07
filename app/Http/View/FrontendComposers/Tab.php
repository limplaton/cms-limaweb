<?php
 

namespace App\Http\View\FrontendComposers;

use Modules\Core\App\Support\Makeable;

class Tab
{
    use Makeable;

    public ?int $displayOrder = null;

    /**
     * Create new Tab instance.
     */
    public function __construct(public string $id, public string $component, public ?string $panelComponent = null)
    {
    }

    /**
     * Add the tab order
     */
    public function order(int $order): static
    {
        $this->displayOrder = $order;

        return $this;
    }

    /**
     * Set the tab panel component
     */
    public function panel(string $component): static
    {
        $this->panelComponent = $component;

        return $this;
    }
}
