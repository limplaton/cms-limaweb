<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\App\Contracts\Resources\AcceptsCustomFields;
use Modules\Core\App\Contracts\Resources\Exportable;
use Modules\Core\App\Contracts\Resources\HasOperations;
use Modules\Core\App\Contracts\Resources\Tableable;
use Modules\Core\App\Facades\Fields;
use Modules\Core\App\Fields\BelongsTo;
use Modules\Core\App\Fields\Boolean;
use Modules\Core\App\Fields\Date;
use Modules\Core\App\Fields\DateTime;
use Modules\Core\App\Fields\Editor;
use Modules\Core\App\Fields\Number;
use Modules\Core\App\Fields\Text;
use Modules\Core\App\Fields\User;
use Modules\Core\App\Filters\Text as TextFilter;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Resource\Resource;
use Modules\Core\App\Table\Table;

class EventResource extends Resource implements AcceptsCustomFields, Exportable, HasOperations, Tableable
{
    public static bool $globallySearchable = true;

    public static $useFields;

    public static string $model = 'Tests\Fixtures\Event';

    public static bool $hasDetailView = true;

    public static function swapFields($fields)
    {
        Fields::flushRegisteredCache();
        static::$useFields = $fields;
    }

    public function table(Builder $query, ResourceRequest $request): Table
    {
        return new EventTable($query, $request);
    }

    public function viewAuthorizedRecordsCriteria(): string
    {
        return OwnEventsCriteria::class;
    }

    public function fields(ResourceRequest $request): array
    {
        if (static::$useFields) {
            return static::$useFields;
        }

        return [
            Text::make('title', 'Title'),
            Editor::make('description', 'Description'),
            Boolean::make('is_all_day', 'All Day'),
            Date::make('date', 'Date'),
            DateTime::make('start', 'Start'),
            DateTime::make('end', 'End'),
            Number::make('total_guests', 'Total Guests'),
            User::make(),
            BelongsTo::make('status', EventStatus::class, 'Status', 'status_id'),
        ];
    }

    public function filters(ResourceRequest $request): array
    {
        return [
            TextFilter::make('title', 'Title'),
        ];
    }

    public static function label(): string
    {
        return 'Events';
    }

    public static function singularLabel(): string
    {
        return 'Event';
    }

    public static function name(): string
    {
        return 'events';
    }

    public static function singularName(): string
    {
        return 'event';
    }

    public function associateableName(): string
    {
        return 'events';
    }

    public function jsonResource(): string
    {
        return EventJsonResource::class;
    }

    public function registerPermissions(): void
    {
        $this->registerCommonPermissions();
    }
}
