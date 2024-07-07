<?php
 

namespace Modules\Core\App\Updater;

trait ExchangesPurchaseKey
{
    protected ?string $purchaseKey = null;

    /**
     * Use the given custom purchase key.
     */
    public function usePurchaseKey(string $key): static
    {
        $this->purchaseKey = $key;

        return $this;
    }

    /**
     * Get the updater purchase key.
     */
    public function getPurchaseKey(): ?string
    {
        return $this->purchaseKey ?: $this->config['purchase_key'];
    }
}
