<?php
 

namespace Modules\Billable\App\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Billable\App\Enums\TaxType;
use Modules\Billable\App\Listeners\TransferProductsUserData;
use Modules\Billable\App\Models\Billable;
use Modules\Billable\App\Models\BillableProduct;
use Modules\Billable\App\Models\Product;
use Modules\Billable\App\Observers\ProductObserver;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Settings\DefaultSettings;
use Modules\Users\App\Events\TransferringUserData;

class BillableServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Billable';

    protected string $moduleNameLower = 'billable';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/migrations'));

        DefaultSettings::addRequired('tax_label', 'TAX');
        DefaultSettings::add('tax_rate', 0);
        DefaultSettings::addRequired('tax_type', 'no_tax');
        DefaultSettings::addRequired('discount_type', 'percent');

        $this->app['events']->listen(TransferringUserData::class, TransferProductsUserData::class);

        $this->app->booted($this->bootModule(...));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'),
            $this->moduleNameLower
        );
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->moduleNameLower);

        $sourcePath = module_path($this->moduleName, 'resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower.'-module-views']);

        $this->loadViewsFrom([...$this->getPublishableViewPaths(), ...[$sourcePath]], $this->moduleNameLower);
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $this->loadTranslationsFrom(module_path($this->moduleName, 'lang'), $this->moduleNameLower);
    }

    /**
     * Boot the module.
     */
    protected function bootModule(): void
    {
        $this->registerResources();
        Product::observe(ProductObserver::class);

        Innoclapps::whenReadyForServing(fn () => Innoclapps::booted($this->shareDataToScript(...)));
    }

    /**
     * Register the module available resources.
     */
    protected function registerResources(): void
    {
        Innoclapps::resources([
            \Modules\Billable\App\Resources\Product::class,
        ]);
    }

    /**
     * Share data to script.
     */
    protected function shareDataToScript(): void
    {
        Innoclapps::provideToScript([
            'settings' => [
                'tax_type' => Billable::defaultTaxType()?->name,
                'tax_label' => BillableProduct::defaultTaxLabel(),
                'tax_rate' => BillableProduct::defaultTaxRate(),
                'discount_type' => BillableProduct::defaultDiscountType(),
            ],
            'taxes' => [
                'types' => TaxType::names(),
            ],
        ]);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Get the publishable view paths.
     */
    private function getPublishableViewPaths(): array
    {
        $paths = [];

        foreach ($this->app['config']->get('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->moduleNameLower)) {
                // $paths[] = $path.'/modules/'.$this->moduleNameLower;
            }
        }

        return $paths;
    }
}
