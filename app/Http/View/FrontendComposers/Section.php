<?php
 

namespace App\Http\View\FrontendComposers;

use Modules\Core\App\Support\Makeable;

class Section
{
    use Makeable;

    /**
     * Indicates whether the section is enabled
     */
    public bool $enabled = true;

    /**
     * Section order
     */
    public ?int $order = null;

    /**
     * Section heading
     */
    public ?string $heading = null;

    /**
     * Create new Section instance
     */
    public function __construct(public string $id, public string $component)
    {
    }

    /**
     * Set the section heading.
     */
    public function heading(string $heading): static
    {
        $this->heading = $heading;

        return $this;
    }
}
