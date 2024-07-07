<?php
 

namespace Modules\Core\App;

class Tools
{
    /**
     * All of the registered tools.
     */
    protected static array $tools = [];

    /**
     * Register new tool.
     */
    public function register(string $key, callable $callback, ?string $description = null): static
    {
        static::$tools[$key] = [
            'display_name' => $key,
            'description' => $description,
            'handler' => $callback,
        ];

        return $this;
    }

    /**
     * Execute the given tool.
     */
    public function execute(string $tool): mixed
    {
        return call_user_func(static::$tools[$tool]['handler']);
    }

    /**
     * Get all of the registered tools.
     */
    public function all(): array
    {
        return static::$tools;
    }

    /**
     * Check if the given tool is registered.
     */
    public function has(string $tool): bool
    {
        return isset(static::$tools[$tool]);
    }
}
