<?php
 

namespace Modules\Core\App\Workflow;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Core\App\Contracts\Workflow\EventTrigger;
use Modules\Core\App\Contracts\Workflow\ModelTrigger;
use Modules\Core\App\Models\Workflow;

class Workflows
{
    /**
     * The event only triggers.
     */
    public static array $eventOnlyListeners = [];

    /**
     * Registered triggers.
     */
    public static array $triggers = [];

    /**
     * Processed workflows actions.
     */
    public static array $processed = [];

    /**
     * Indicates whether the workflows are running.
     */
    protected static bool $workflowRunning = false;

    /**
     * Queued workflows.
     */
    protected static array $queue = [];

    /**
     * Process for running the given workflow.
     */
    public static function process(Workflow $workflow, array $data = []): void
    {
        if (! static::workflowActionCanBeExecuted($workflow)) {
            return;
        }

        [$action, $trigger] = static::prepareActionForDispatch($workflow, $data);

        (function ($method) use ($action, $trigger, $workflow) {
            static::$processed[$action::class] = [
                'action' => $action,
                'workflow' => $workflow,
                'trigger' => $trigger,
            ];

            ProcessWorkflowAction::{$method}($action);
        })($action instanceof ShouldQueue ? 'dispatch' : 'dispatchSync');
    }

    /**
     * Process the queued workflows.
     */
    public static function processQueue(): void
    {
        foreach (static::$queue as $queue) {
            static::process($queue['workflow'], $queue['data']);
        }

        static::$queue = [];
    }

    /**
     * Add the workflow to the internal queue.
     */
    public static function addToQueue(Workflow $workflow, array $data = []): void
    {
        if (! static::workflowActionCanBeExecuted($workflow)) {
            return;
        }

        static::$queue[] = [
            'workflow' => $workflow,
            'data' => $data,
        ];
    }

    /**
     * Check whether the workflow action can be executed.
     */
    protected static function workflowActionCanBeExecuted(Workflow $workflow): bool
    {
        return static::newTriggerInstance($workflow->trigger_type)->getAction($workflow->action_type)::allowedForExecution();
    }

    /**
     * Get the available triggers classes.
     */
    public static function availableTriggers(): array
    {
        return static::$triggers;
    }

    /**
     * Get the available triggers classes instance.
     */
    public static function triggersInstance(): Collection
    {
        return collect(static::availableTriggers())->map(fn ($trigger) => resolve($trigger));
    }

    /**
     * Create new trigger instance by a given trigger class.
     */
    public static function newTriggerInstance(string $class): Trigger
    {
        return collect(static::availableTriggers())
            ->filter(fn ($trigger) => $trigger === $class)
            ->map(fn ($trigger) => resolve($trigger))
            ->first();
    }

    /**
     * Get triggers by a given model.
     */
    public static function triggersByModel(string|Model $model): Collection
    {
        return static::triggersInstance()
            ->whereInstanceOf(ModelTrigger::class)
            ->filter(
                fn ($trigger) => $trigger::model() === (! is_string($model) ? $model::class : $model)
            );
    }

    /**
     * Register the given triggers.
     */
    public static function triggers(array $triggers): void
    {
        static::$triggers = array_merge(static::$triggers, $triggers);
    }

    /**
     * Set whether the workflows are running.
     */
    public static function workflowRunning(bool $value = true): void
    {
        static::$workflowRunning = $value;
    }

    /**
     * Check whether the workflows are running.
     */
    public static function isWorkflowRunning(): bool
    {
        return static::$workflowRunning;
    }

    /**
     * Register laravel event based triggers.
     */
    public static function registerEventOnlyTriggersListeners(): void
    {
        static::$eventOnlyListeners = collect(static::availableTriggers())
            ->filter(function ($trigger) {
                return is_a($trigger, EventTrigger::class, true) &&
                ! is_a($trigger, ModelTrigger::class, true);
            })->map(fn ($trigger) => [
                'trigger' => $trigger,
                'event' => $trigger::event(),
            ])->values()->all();
    }

    /**
     * Prepare the given workflow and data for execution.
     */
    protected static function prepareActionForDispatch(Workflow $workflow, array $data): array
    {
        // We will merge the workflow data, the provided custom data and the
        // actual workflow as data to be available in the action that will be executed for the workflow
        $actionData = array_merge(
            $workflow->data,
            $data,
            ['workflow' => $workflow]
        );

        $trigger = static::newTriggerInstance($workflow->trigger_type);

        $action = $trigger->getAction($workflow->action_type)->setData($actionData);

        return [$action, $trigger];
    }
}
