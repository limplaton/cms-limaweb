<?php
 

namespace Modules\Core\App\Settings\Stores;

use Illuminate\Redis\RedisManager;
use Illuminate\Support\Arr;

/**
 * @codeCoverageIgnore
 * NOT USED YET
 */
class RedisStore extends AbstractStore
{
    /**
     * The redis manager.
     *
     * @var \Illuminate\Redis\RedisManager
     */
    protected $manager;

    /**
     * Fire the post options to customize the store.
     */
    protected function postOptions(array $options)
    {
        $this->manager = new RedisManager(
            $this->app,
            Arr::pull($options, 'client', 'predis'),
            $options
        );
    }

    /**
     * Read the data from the store.
     */
    protected function read(): array
    {
        $data = $this->command('get', ['settings']);

        return is_string($data) ? json_decode($data, true) : [];
    }

    /**
     * Write the data into the store.
     */
    protected function write(array $data): void
    {
        $this->command('set', ['settings', json_encode($data)]);
    }

    /**
     * Get a Redis connection by name.
     *
     *
     * @return \Illuminate\Redis\Connections\Connection
     */
    protected function connection(?string $name = null)
    {
        return $this->manager->connection($name);
    }

    /**
     * Run a command against the Redis database.
     *
     *
     * @return mixed
     */
    protected function command(string $method, array $parameters = [])
    {
        return $this->connection()->command($method, $parameters);
    }
}
