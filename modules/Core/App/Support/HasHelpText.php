<?php
 

namespace Modules\Core\App\Support;

trait HasHelpText
{
    /**
     * Help text
     */
    public ?string $helpText = null;

    /**
     * Set filter help text
     */
    public function help(?string $text): static
    {
        $this->helpText = $text;

        return $this;
    }
}
