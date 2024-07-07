<?php
 

namespace Modules\Core\App\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use Modules\Core\App\Support\ReCaptcha;

class ReCaptchaServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('recaptcha', function (Application $app) {
            return (new ReCaptcha(Request::instance()))
                ->setSiteKey($app['config']->get('core.recaptcha.site_key'))
                ->setSecretKey($app['config']->get('core.recaptcha.secret_key'))
                ->setSkippedIps($app['config']->get('core.recaptcha.ignored_ips', []));
        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return ['recaptcha'];
    }
}
