<?php
 

namespace Modules\Core\App\Contracts\Workflow;

interface EventTrigger
{
    /**
     * The event name the trigger should be triggered
     */
    public static function event(): string;
}
