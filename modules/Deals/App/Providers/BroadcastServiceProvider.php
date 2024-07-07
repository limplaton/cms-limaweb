<?php
 

namespace Modules\Deals\App\Providers;

use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        require module_path('Deals', 'routes/channels.php');
    }
}
