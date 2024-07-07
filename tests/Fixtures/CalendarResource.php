<?php

namespace Tests\Fixtures;

use Modules\Core\App\Resource\Resource;

class CalendarResource extends Resource
{
    public static string $model = 'Tests\Fixtures\Calendar';

    public static function label(): string
    {
        return 'Calendars';
    }

    public static function singularLabel(): string
    {
        return 'Calendar';
    }

    public static function name(): string
    {
        return 'calendars';
    }

    public static function singularName(): string
    {
        return 'calendar';
    }

    public function associateableName(): string
    {
        return 'calendars';
    }

    public function jsonResource(): string
    {
        return CalendarJsonResource::class;
    }

    public function registerPermissions(): void
    {
        $this->registerCommonPermissions();
    }
}
