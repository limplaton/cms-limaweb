<?php
 

namespace Modules\Calls\App\Resources;

use Modules\Calls\App\Http\Resources\CallOutcomeResource;
use Modules\Core\App\Contracts\Resources\HasOperations;
use Modules\Core\App\Fields\ColorSwatch;
use Modules\Core\App\Fields\Text;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Resource\Resource;
use Modules\Core\App\Rules\StringRule;

class CallOutcome extends Resource implements HasOperations
{
    /**
     * The column the records should be default ordered by when retrieving
     */
    public static string $orderBy = 'name';

    /**
     * The model the resource is related to
     */
    public static string $model = 'Modules\Calls\App\Models\CallOutcome';

    /**
     * Set the available resource fields
     */
    public function fields(ResourceRequest $request): array
    {
        return [
            Text::make('name', __('calls::call.outcome.name'))->rules(['required', StringRule::make()])->unique(static::$model),
            ColorSwatch::make('swatch_color', __('core::app.colors.color')),
        ];
    }

    /**
     * Get the json resource that should be used for json response
     */
    public function jsonResource(): string
    {
        return CallOutcomeResource::class;
    }

    /**
     * Get the displayable singular label of the resource
     */
    public static function singularLabel(): string
    {
        return __('calls::call.outcome.call_outcome');
    }

    /**
     * Get the displayable label of the resource
     */
    public static function label(): string
    {
        return __('calls::call.outcome.outcomes');
    }
}
