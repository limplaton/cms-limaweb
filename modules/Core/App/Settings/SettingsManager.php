<?php
 

namespace Modules\Core\App\Settings;

use Illuminate\Support\Arr;
use Illuminate\Support\Manager;
use Modules\Core\App\Settings\Contracts\Manager as SettingsManagerContract;
use Modules\Core\App\Settings\Contracts\Store as StoreContract;

class SettingsManager extends Manager implements SettingsManagerContract
{
    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('settings.default', 'json');
    }

    /**
     * Register a new store.
     */
    public function registerStore(string $driver, array $params): static
    {
        return $this->extend($driver, function () use ($params): StoreContract {
            return $this->container->make($params['driver'], [
                'options' => Arr::get($params, 'options', []),
            ]);
        });
    }
}
