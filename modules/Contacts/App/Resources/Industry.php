<?php
 

namespace Modules\Contacts\App\Resources;

use Modules\Contacts\App\Http\Resources\IndustryResource;
use Modules\Core\App\Contracts\Resources\HasOperations;
use Modules\Core\App\Fields\Text;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Resource\Resource;
use Modules\Core\App\Rules\StringRule;

class Industry extends Resource implements HasOperations
{
    /**
     * The column the records should be default ordered by when retrieving
     */
    public static string $orderBy = 'name';

    /**
     * The model the resource is related to
     */
    public static string $model = 'Modules\Contacts\App\Models\Industry';

    /**
     * Set the available resource fields
     */
    public function fields(ResourceRequest $request): array
    {
        return [
            Text::make('name', __('contacts::company.industry.industry'))->rules(['required', StringRule::make()])->unique(static::$model),
        ];
    }

    /**
     * Get the displayable singular label of the resource
     */
    public static function singularLabel(): string
    {
        return __('contacts::company.industry.industry');
    }

    /**
     * Get the displayable label of the resource
     */
    public static function label(): string
    {
        return __('contacts::company.industry.industries');
    }

    /**
     * Get the json resource that should be used for json response
     */
    public function jsonResource(): string
    {
        return IndustryResource::class;
    }
}
