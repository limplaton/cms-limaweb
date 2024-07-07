<?php
 

namespace Modules\ThemeStyle\App\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Facades\SettingsMenu;
use Modules\Core\App\Settings\SettingsMenuItem;

class ThemeStyleServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'ThemeStyle';

    protected string $moduleNameLower = 'themestyle';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/migrations'));

        Innoclapps::style('theme-style', url('theme-style'));

        $this->app->booted($this->bootModule(...));
    }

    /**
     * Register the service providers.
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
        $this->publishes([
            module_path($this->moduleName, 'config/config.php') => config_path($this->moduleNameLower.'.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'), $this->moduleNameLower
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
        Innoclapps::whenReadyForServing(function () {
            Innoclapps::booted(function () {
                SettingsMenu::add('system', SettingsMenuItem::make(
                    __('themestyle::style.theme_style'),
                    '/settings/theme-style'
                )->setId('themestyle'));
            });

            Innoclapps::booted($this->shareDataToScript(...));
        });
    }

    /**
     * Share data to script.
     */
    protected function shareDataToScript(): void
    {
        Innoclapps::provideToScript([
            'themestyle' => [],
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
