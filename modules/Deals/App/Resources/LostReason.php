<?php
 

namespace Modules\Deals\App\Resources;

use Modules\Core\App\Contracts\Resources\HasOperations;
use Modules\Core\App\Fields\Textarea;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Resource\Resource;
use Modules\Core\App\Rules\StringRule;
use Modules\Deals\App\Http\Resources\LostReasonResource;

class LostReason extends Resource implements HasOperations
{
    /**
     * The column the records should be default ordered by when retrieving
     */
    public static string $orderBy = 'name';

    /**
     * The model the resource is related to
     */
    public static string $model = 'Modules\Deals\App\Models\LostReason';

    /**
     * Set the available resource fields
     */
    public function fields(ResourceRequest $request): array
    {
        return [
            Textarea::make('name', __('deals::deal.lost_reasons.name'))->rules(['required', StringRule::make()])->unique(static::$model),
        ];
    }

    /**
     * Get the json resource that should be used for json response
     */
    public function jsonResource(): string
    {
        return LostReasonResource::class;
    }

    /**
     * Get the displayable singular label of the resource
     */
    public static function singularLabel(): string
    {
        return __('deals::deal.lost_reasons.lost_reason');
    }

    /**
     * Get the displayable label of the resource
     */
    public static function label(): string
    {
        return __('deals::deal.lost_reasons.lost_reasons');
    }
}
