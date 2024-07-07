<?php
 

namespace Modules\Core\App\Card;

/** @mixin \Modules\Core\App\Card\Card */
trait FloatsResource
{
    protected ?array $floatingResource = null;

    public function floatResourceInEditMode(string $resourceName): static
    {
        $this->floatingResource = ['resourceName' => $resourceName, 'mode' => 'edit'];

        return $this;
    }

    public function floatResourceInDetailMode(string $resourceName): static
    {
        $this->floatingResource = ['resourceName' => $resourceName, 'mode' => 'detail'];

        return $this;
    }
}
