<?php
 

namespace Modules\Documents\App\Resources;

use Modules\Core\App\Contracts\Resources\HasOperations;
use Modules\Core\App\Criteria\VisibleModelsCriteria;
use Modules\Core\App\Fields\ColorSwatch;
use Modules\Core\App\Fields\Text;
use Modules\Core\App\Fields\VisibilityGroup;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Resource\Resource;
use Modules\Core\App\Rules\StringRule;
use Modules\Documents\App\Http\Resources\DocumentTypeResource;

class DocumentType extends Resource implements HasOperations
{
    /**
     * The column the records should be default ordered by when retrieving
     */
    public static string $orderBy = 'name';

    /**
     * The model the resource is related to
     */
    public static string $model = 'Modules\Documents\App\Models\DocumentType';

    /**
     * Provide the criteria that should be used to query only records that the logged-in user is authorized to view
     */
    public function viewAuthorizedRecordsCriteria(): string
    {
        return VisibleModelsCriteria::class;
    }

    /**
     * Get the json resource that should be used for json response
     */
    public function jsonResource(): string
    {
        return DocumentTypeResource::class;
    }

    /**
     * Provide the available resource fields
     */
    public function fields(ResourceRequest $request): array
    {
        return [
            Text::make('name', __('documents::document.type.name'))
                ->rules(['required', StringRule::make()])
                ->unique(static::$model)
                ->showValueWhenUnauthorizedToView(),
            ColorSwatch::make('swatch_color', __('core::app.colors.color'))
                ->showValueWhenUnauthorizedToView(),
            VisibilityGroup::make('visibility_group'),
        ];
    }

    /**
     * Get the displayable singular label of the resource
     */
    public static function singularLabel(): string
    {
        return __('documents::document.type.type');
    }

    /**
     * Get the displayable label of the resource
     */
    public static function label(): string
    {
        return __('documents::document.type.types');
    }
}
