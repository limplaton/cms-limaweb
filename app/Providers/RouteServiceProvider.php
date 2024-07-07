<?php
 

namespace App\Providers;

use App\Http\Middleware\PreventInstallationWhenInstalled;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Modules\Core\App\Http\Middleware\PreventRequestsWhenMigrationNeeded;
use Modules\Core\App\Http\Middleware\PreventRequestsWhenUpdateNotFinished;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/deals';

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware([PreventInstallationWhenInstalled::class, 'web'])
                ->prefix(\DetachedHelper::INSTALL_ROUTE_PREFIX)
                ->withoutMiddleware([PreventRequestsWhenMigrationNeeded::class, PreventRequestsWhenUpdateNotFinished::class])
                ->group(base_path('routes/install.php'));

            Route::prefix(\Modules\Core\App\Application::API_PREFIX)
                ->middleware('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(90)->by($request->user()?->id ?: $request->ip());
        });
    }
}
