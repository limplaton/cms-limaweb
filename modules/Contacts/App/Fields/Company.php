<?php
 

namespace Modules\Contacts\App\Fields;

use Modules\Contacts\App\Http\Resources\CompanyResource;
use Modules\Contacts\App\Models\Company as CompanyModel;
use Modules\Core\App\Fields\BelongsTo;

class Company extends BelongsTo
{
    /**
     * Create new instance of Company field
     *
     * @param  string  $relationName  The relation name, snake case format
     * @param  string  $label  Custom label
     * @param  string  $foreignKey  Custom foreign key
     */
    public function __construct($relationName = 'company', $label = null, $foreignKey = null)
    {
        parent::__construct($relationName, CompanyModel::class, $label ?? __('contacts::company.company'), $foreignKey);

        $this->setJsonResource(CompanyResource::class)
            ->lazyLoad('/companies', ['order' => 'created_at|desc'])
            ->onOptionClick('float', ['resourceName' => 'companies'])
            ->async('/companies/search');
    }
}
