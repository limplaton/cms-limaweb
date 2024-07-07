<?php
 

namespace Modules\Core\App\Providers;

use Illuminate\Container\Container;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Modules\Core\App\Support\HtmlPurifier\Purifier;

class PurifierServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->singleton('purifier', function (Container $app) {
            return new Purifier($app['files'], $app['config']);
        });

        $this->app->alias('purifier', Purifier::class);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return ['purifier'];
    }
}
