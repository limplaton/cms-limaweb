<?php
 

namespace Modules\Calls\App\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Modules\Calls\App\VoIP\Contracts\VoIPClient;
use Modules\Calls\App\VoIP\VoIPManager;

class VoIPServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(VoIPClient::class, function (Application $app) {
            return new VoIPManager($app);
        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [VoIPClient::class];
    }
}
