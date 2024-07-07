<?php
 

namespace Modules\Core\App\Contracts\Workflow;

interface FieldChangeTrigger
{
    /**
     * The field to track changes on
     */
    public static function field(): string;

    /**
     * Provide the change field
     *
     * @return \Modules\Core\App\Fields\Field
     */
    public static function changeField();
}
