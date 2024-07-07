<?php
 

namespace Modules\Contacts\App\Resources;

use Modules\Contacts\App\Http\Resources\SourceResource;
use Modules\Core\App\Contracts\Resources\HasOperations;
use Modules\Core\App\Fields\Text;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Resource\Resource;
use Modules\Core\App\Rules\StringRule;

class Source extends Resource implements HasOperations
{
    /**
     * The column the records should be default ordered by when retrieving
     */
    public static string $orderBy = 'name';

    /**
     * The model the resource is related to
     */
    public static string $model = 'Modules\Contacts\App\Models\Source';

    /**
     * Get the json resource that should be used for json response
     */
    public function jsonResource(): string
    {
        return SourceResource::class;
    }

    /**
     * Set the available resource fields
     */
    public function fields(ResourceRequest $request): array
    {
        return [
            Text::make('name', __('contacts::source.source'))->rules(['required', StringRule::make()])->unique(static::$model),
        ];
    }

    /**
     * Get the displayable singular label of the resource
     */
    public static function singularLabel(): string
    {
        return __('contacts::source.source');
    }

    /**
     * Get the displayable label of the resource
     */
    public static function label(): string
    {
        return __('contacts::source.sources');
    }
}
