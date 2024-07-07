<?php
 

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Core\App\Settings\DefaultSettings;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app['events']->listen([Login::class, Logout::class], function (object $event) {
            session()->put('locale', $event->user->preferredLocale());
        });

        $this->increaseCliMemoryLimit();

        $this->forceSsl();

        Model::preventLazyLoading(! app()->isProduction());

        Schema::defaultStringLength(191);

        JsonResource::withoutWrapping();

        DefaultSettings::add('disable_password_forgot', false);

        View::composer('components/layouts/auth', \Modules\Core\App\Http\View\Composers\AppComposer::class);
    }

    protected function increaseCliMemoryLimit(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $memoryLimit = $this->app['config']->get('app.cli_memory_limit');

        if (! empty($memoryLimit)) {
            \DetachedHelper::raiseMemoryLimit($memoryLimit);
        }
    }

    protected function forceSsl(): void
    {
        if (str_starts_with($this->app['config']->get('app.url'), 'https://')) {
            $this->app['config']->set('app.force_ssl', true);
        }

        if ($this->app['config']->get('app.force_ssl')) {
            URL::forceScheme('https');
        }
    }
}
