<?php
 

namespace Modules\Contacts\App\Fields;

use Modules\Contacts\App\Http\Resources\SourceResource;
use Modules\Contacts\App\Models\Source as SourceModel;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Fields\BelongsTo;

class Source extends BelongsTo
{
    /**
     * Create new instance of Source field
     *
     * @param  string  $label  Custom label
     */
    public function __construct($label = null)
    {
        parent::__construct('source', SourceModel::class, $label ?? __('contacts::source.source'));

        $this->setJsonResource(SourceResource::class)
            ->options(Innoclapps::resourceByModel(SourceModel::class))
            ->acceptLabelAsValue();
    }
}
