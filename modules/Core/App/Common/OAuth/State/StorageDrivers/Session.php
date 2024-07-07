<?php
 

namespace Modules\Core\App\Common\OAuth\State\StorageDrivers;

use Illuminate\Support\Facades\Session as Storage;
use Modules\Core\App\Contracts\OAuth\StateStorage;

class Session implements StateStorage
{
    /**
     * The state session key
     *
     * @var string
     */
    protected $key = 'oauth2state';

    /**
     * Get state from storage
     */
    public function get(): ?string
    {
        return Storage::get($this->key);
    }

    /**
     * Put state in storage
     *
     * @param  string  $value
     */
    public function put($value): void
    {
        Storage::put($this->key, $value);
    }

    /**
     * Check whether there is stored state
     */
    public function has(): bool
    {
        return Storage::has($this->key);
    }

    /**
     * Forget the remembered state from storage
     */
    public function forget(): void
    {
        Storage::forget($this->key);
    }
}
