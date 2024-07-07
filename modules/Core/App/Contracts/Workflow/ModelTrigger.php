<?php
 

namespace Modules\Core\App\Contracts\Workflow;

interface ModelTrigger
{
    /**
     * The model class name the trigger is related to
     */
    public static function model(): string;
}
