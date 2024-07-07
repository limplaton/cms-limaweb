<?php
 

namespace Modules\Core\App\Workflow;

use Closure;
use JsonSerializable;
use Modules\Core\App\Contracts\Workflow\ModelTrigger;

abstract class Action implements JsonSerializable
{
    /**
     * The data intended for the action
     *
     * @var object|null
     */
    protected $data;

    /**
     * The trigger the action is composed from
     *
     * @var \Modules\Core\App\Workflow\Trigger|null
     */
    protected $trigger;

    protected static bool $disabled = false;

    /**
     * Provide the action name
     */
    abstract public static function name(): string;

    /**
     * Run the trigger
     *
     * @return mixed
     */
    abstract public function run();

    /**
     * Action available fields
     */
    public function fields(): array
    {
        return [];
    }

    /**
     * Check whether the action can be executed
     */
    public static function allowedForExecution(): bool
    {
        return static::$disabled === false;
    }

    /**
     * Disable the actions executions.
     */
    public static function disableExecutions(bool $value = true): void
    {
        static::$disabled = $value;
    }

    /**
     * Check whether the action is triggered via model
     */
    public function viaModelTrigger(): bool
    {
        return $this->trigger() instanceof ModelTrigger;
    }

    /**
     * Get the action trigger
     *
     * @return \Modules\Core\App\Workflow\Trigger|\Modules\Core\App\Contracts\Workflow\ModelTrigger|\Modules\Core\App\Contracts\Workflow\EventTrigger|null
     */
    public function trigger(): ?Trigger
    {
        return $this->trigger;
    }

    /**
     * Set the action trigger
     */
    public function setTrigger(Trigger $trigger): static
    {
        $this->trigger = $trigger;

        return $this;
    }

    /**
     * Register execution callback
     */
    public function executing(Closure $callback): static
    {
        $trigger = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['class'];

        $trigger::registerActionExecutingEvent(get_called_class(), $callback);

        return $this;
    }

    /**
     * Get the action identifier
     */
    public static function identifier(): string
    {
        return get_called_class();
    }

    /**
     * Set the trigger data
     *
     * @param  array  $data
     * @return $self
     */
    public function setData($data): static
    {
        $this->data = (object) $data;

        return $this;
    }

    /**
     * Determine if an attribute exists on data.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->data->{$key});
    }

    /**
     * Dynamically get properties from the data.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->data->{$key};
    }

    /**
     * Dynamically set properties to the data.
     *
     * @param  string  $name
     * @param  mixed  $value
     * @return void
     */
    public function __set($name, $value)
    {
        $this->data->{$name} = $value;
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return [
            'identifier' => static::identifier(),
            'name' => static::name(),
            'fields' => $this->fields(),
        ];
    }
}
