<?php
 

namespace Modules\Deals\App\Fields;

use Modules\Core\App\Fields\BelongsTo;
use Modules\Deals\App\Http\Resources\DealResource;
use Modules\Deals\App\Models\Deal as DealModel;

/**
 * @codeCoverageIgnore
 */
class Deal extends BelongsTo
{
    /**
     * Create new instance of Deal field
     *
     * @param  string  $relationName  The relation name, snake case format
     * @param  string  $label  Custom label
     * @param  string  $foreignKey  Custom foreign key
     */
    public function __construct($relationName = 'deal', $label = null, $foreignKey = null)
    {
        parent::__construct($relationName, DealModel::class, $label ?? __('deals::deal.deal'), $foreignKey);

        $this->setJsonResource(DealResource::class)
            ->async('/deals/search');
    }
}
