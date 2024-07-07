<?php
 

namespace Modules\WebForms\App\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Facades\MailableTemplates;
use Modules\Core\App\Facades\SettingsMenu;
use Modules\Core\App\Settings\SettingsMenuItem;
use Modules\Users\App\Events\TransferringUserData;
use Modules\WebForms\App\Listeners\TransferWebFormUserData;

class WebFormsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'WebForms';

    protected string $moduleNameLower = 'webforms';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();

        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/migrations'));

        MailableTemplates::register([
            \Modules\WebForms\App\Mail\WebFormSubmitted::class,
        ]);

        $this->app['events']->listen(TransferringUserData::class, TransferWebFormUserData::class);

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
        Innoclapps::whenReadyForServing(function () {
            Innoclapps::booted(function () {
                SettingsMenu::register(
                    SettingsMenuItem::make(__('webforms::form.forms'), '/settings/forms', 'MenuAlt3')->order(30),
                    'web-forms'
                );
            });
        });
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
