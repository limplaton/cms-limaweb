<?php
 

namespace Modules\Core\App\Settings\Stores;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Crypt;
use Modules\Core\App\Settings\Contracts\Store;
use Modules\Core\App\Settings\Utilities\Arr;

abstract class AbstractStore implements Store
{
    /**
     * The settings data.
     */
    protected array $data = [];

    /**
     * Original settings
     */
    protected array $original = [];

    /**
     * The settings keys that should be encrypted in storage
     */
    protected array $encrypted;

    /**
     * Whether the store has changed since it was last loaded.
     */
    protected bool $unsaved = false;

    /**
     * Whether the settings data are loaded.
     */
    protected bool $loaded = false;

    /**
     * AbstractStore constructor.
     */
    public function __construct(protected Application $app, array $options = [])
    {
        $this->encrypted = $app['config']->get('settings.encrypted', []);

        $this->postOptions($options);
    }

    /**
     * Fire the post options to customize the store.
     */
    abstract protected function postOptions(array $options);

    /**
     * Read the data from the store.
     */
    abstract protected function read(): array;

    /**
     * Write the data into the store.
     */
    abstract protected function write(array $data): void;

    /**
     * Get a specific key from the settings data.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->checkLoaded();

        $value = Arr::get($this->data, $key, $default);

        return $this->parseValue($value, $key);
    }

    /**
     * Determine if a key exists in the settings data.
     */
    public function has(string $key): bool
    {
        $this->checkLoaded();

        return Arr::has($this->data, $key);
    }

    /**
     * Set a specific key to a value in the settings data.
     */
    public function set(string|array $key, mixed $value = null): static
    {
        $this->checkLoaded();

        $this->unsaved = true;

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->setValue($k, $v);
            }
        } else {
            $this->setValue($key, $value);
        }

        return $this;
    }

    /**
     * Unset a key in the settings data.
     */
    public function forget(string $key): static
    {
        $this->checkLoaded();

        $this->unsaved = true;

        Arr::forget($this->data, $key);

        return $this;
    }

    /**
     * Flushing all data.
     */
    public function flush(): static
    {
        $this->unsaved = true;
        $this->data = [];
        $this->original = [];

        return $this;
    }

    /**
     * Get all settings data.
     */
    public function all(): array
    {
        $this->checkLoaded();

        return Arr::map($this->data, fn ($value, $key) => $this->parseValue($value, $key));
    }

    /**
     * Get all of the currently available settings keys.
     */
    public function keys(): array
    {
        $this->checkLoaded();

        return array_keys(Arr::dot($this->data));
    }

    /**
     * Save any changes done to the settings data.
     */
    public function save(): static
    {
        if (! $this->isSaved()) {
            $this->write($this->data);
            $this->original = $this->data;
            $this->unsaved = false;
        }

        return $this;
    }

    /**
     * Check if the data is saved.
     */
    public function isSaved(): bool
    {
        return ! $this->unsaved;
    }

    /**
     * Parse the given value
     *
     * @param  string|null  $value
     * @param  string  $key
     * @return mixed
     */
    protected function parseValue($value, $key)
    {
        if (in_array($key, $this->encrypted) && ! empty($value)) {
            try {
                return Crypt::decryptString($value);
            } catch (DecryptException) {
                return null;
            }
        }

        return $value;
    }

    /**
     * Set the value to the store
     *
     * @param  string  $key
     * @param  string  $value
     */
    protected function setValue($key, $value): void
    {
        if (in_array($key, $this->encrypted) && ! empty($value)) {
            $value = Crypt::encryptString($value);
        }

        Arr::set($this->data, $key, $value);
    }

    /**
     * Check if the settings data has been loaded.
     */
    protected function checkLoaded(): void
    {
        if ($this->isLoaded()) {
            return;
        }

        $this->data = $this->read();
        $this->original = $this->data;
        $this->loaded = true;
    }

    /**
     * Reset the loaded status.
     */
    protected function resetLoaded(): void
    {
        $this->loaded = false;
    }

    /**
     * Check if the data is loaded.
     */
    protected function isLoaded(): bool
    {
        return $this->loaded;
    }
}
