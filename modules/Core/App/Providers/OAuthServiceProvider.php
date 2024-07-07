<?php
 

namespace Modules\Core\App\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Modules\Core\App\Common\OAuth\State\StateStorageManager;
use Modules\Core\App\Contracts\OAuth\StateStorage;

class OAuthServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(StateStorage::class, function (Application $app) {
            return new StateStorageManager($app);
        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [StateStorage::class];
    }
}
