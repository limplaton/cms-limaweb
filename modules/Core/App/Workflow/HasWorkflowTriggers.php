<?php
 

namespace Modules\Core\App\Workflow;

use Illuminate\Support\Collection;
use Modules\Core\App\Contracts\Workflow\EventTrigger;
use Modules\Core\App\Contracts\Workflow\FieldChangeTrigger;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Models\Workflow;

/** @mixin \Modules\Core\App\Models\Model */
trait HasWorkflowTriggers
{
    /**
     * Register model triggers events
     */
    protected static function bootHasWorkflowTriggers(): void
    {
        foreach (static::getModelEventTriggers() as $trigger) {
            static::{$trigger::event()}(function (self $model) use ($trigger) {
                foreach (static::getTriggerWorkflows($trigger::identifier()) as $workflow) {
                    // We will queue the workflow to be executed in the middleware
                    // just before the response is sent to the browser
                    // this will allow any associations or data added to the model
                    // after the model event to be available to the workflow action
                    Workflows::addToQueue($workflow, [
                        'model' => $model,
                        'resource' => Innoclapps::resourceByModel($model),
                    ]);
                }
            });
        }

        foreach (static::getFieldChangeEventTriggers() as $trigger) {
            static::updated(function ($model) use ($trigger) {
                foreach (static::getTriggerWorkflows($trigger::identifier()) as $workflow) {
                    if (static::hasWorkflowFieldChanged($workflow, $model, $trigger)) {
                        Workflows::process($workflow, [
                            'model' => $model,
                            'resource' => Innoclapps::resourceByModel($model),
                        ]);
                    }
                }
            });
        }
    }

    /**
     * Check whether the model field has changed
     *
     * @param  \Modules\Core\App\Models\Workflow  $workflow
     * @param  $this  $model
     */
    protected static function hasWorkflowFieldChanged($workflow, $model, FieldChangeTrigger $trigger): bool
    {
        $value = $model->{$trigger::field()};
        $original = $model->getOriginal($trigger::field());
        $expected = $workflow->data[$trigger::changeField()->attribute];

        if ($value == $original) {
            return false;
        }

        if ($model->isEnumCastable($trigger::field())) {
            return is_int($expected) ? $value->value === $expected : $value->name === $expected;
        }

        return $value == $expected;
    }

    /**
     * Get the triggers which are triggered on specific event
     */
    protected static function getModelEventTriggers(): Collection
    {
        return Workflows::triggersByModel(static::class)->whereInstanceOf(EventTrigger::class);
    }

    /**
     * Get the triggers which are triggered on specific event
     *
     * @return \Illuminate\Support\Collection
     */
    protected static function getFieldChangeEventTriggers()
    {
        return Workflows::triggersByModel(static::class)->whereInstanceOf(FieldChangeTrigger::class);
    }

    /**
     * Get the trigger saved workflows
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected static function getTriggerWorkflows(string $trigger)
    {
        return once(function () use ($trigger) {
            return Workflow::byTrigger($trigger)->active()->get();
        });
    }
}
