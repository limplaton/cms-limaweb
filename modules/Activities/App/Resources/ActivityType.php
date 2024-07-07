<?php
 

namespace Modules\Activities\App\Resources;

use Modules\Activities\App\Http\Resources\ActivityTypeResource;
use Modules\Core\App\Contracts\Resources\HasOperations;
use Modules\Core\App\Fields\ColorSwatch;
use Modules\Core\App\Fields\IconPicker;
use Modules\Core\App\Fields\Text;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Resource\Resource;
use Modules\Core\App\Rules\StringRule;

class ActivityType extends Resource implements HasOperations
{
    /**
     * The column the records should be default ordered by when retrieving
     */
    public static string $orderBy = 'name';

    /**
     * The model the resource is related to
     */
    public static string $model = 'Modules\Activities\App\Models\ActivityType';

    /**
     * Get the json resource that should be used for json response
     */
    public function jsonResource(): string
    {
        return ActivityTypeResource::class;
    }

    /**
     * Set the available resource fields
     */
    public function fields(ResourceRequest $request): array
    {
        return [
            Text::make('name', __('activities::activity.type.name'))->rules(['required', StringRule::make()])->unique(static::$model),
            IconPicker::make('icon', __('activities::activity.type.icon'))->rules(['required', StringRule::make()->max(50)])->unique(static::$model),
            ColorSwatch::make('swatch_color', __('core::app.colors.color'))->rules('required'), // required for calendar color
        ];
    }

    /**
     * Get the displayable singular label of the resource
     */
    public static function singularLabel(): string
    {
        return __('activities::activity.type.type');
    }

    /**
     * Get the displayable label of the resource
     */
    public static function label(): string
    {
        return __('activities::activity.type.types');
    }
}
