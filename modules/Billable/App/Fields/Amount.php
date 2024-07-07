<?php
 

namespace Modules\Billable\App\Fields;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\App\Fields\Numeric;
use Modules\Core\App\Table\Column;

class Amount extends Numeric
{
    public bool $onlyProducts = false;

    public bool $excludeFromBulkEdit = true;

    /**
     * Get the field form component.
     */
    public function indexComponent(): ?string
    {
        return 'index-billable-amount-field';
    }

    /**
     * Get the field detail component.
     */
    public function detailComponent(): ?string
    {
        return 'detail-billable-amount-field';
    }

    /**
     * Provide the column used for index
     */
    public function indexColumn(): Column
    {
        return parent::indexColumn()->withCount([
            'products' => fn (Builder $query) => $query->withoutGlobalScope('displayOrder'),
        ]);
    }

    /**
     * Force the user to select products instead of giving option to manually modify the value.
     */
    public function onlyProducts(): static
    {
        $this->onlyProducts = true;

        return $this;
    }

    /**
     * Serialize for front end
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'onlyProducts' => $this->onlyProducts,
        ]);
    }
}
