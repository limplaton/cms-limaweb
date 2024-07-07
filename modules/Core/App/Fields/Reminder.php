<?php
 

namespace Modules\Core\App\Fields;

class Reminder extends Field
{
    /**
     * Field component.
     */
    public static $component = 'reminder-field';

    /**
     * Indicates whether to allow the user to cancel the reminder
     */
    public function cancelable(): static
    {
        $this->rules('nullable');

        $this->withMeta([__FUNCTION__ => true]);

        return $this;
    }
}
