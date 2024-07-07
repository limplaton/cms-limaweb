<?php
 

namespace Modules\Translator\App\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Facades\SettingsMenu;
use Modules\Core\App\Facades\Tools;
use Modules\Core\App\Settings\SettingsMenuItem;
use Modules\Core\App\Updater\Events\UpdateFinalized;
use Modules\Translator\App\Translator;

class TranslatorServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Translator';

    protected string $moduleNameLower = 'translator';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/migrations'));

        $this->app['events']->listen(UpdateFinalized::class, Translator::generateJsonLanguageFile(...));

        $this->registerCommands();

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
        $sourcePath = module_path($this->moduleName, 'resources/views');

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
            Tools::register(
                'json-language',
                Translator::generateJsonLanguageFile(...),
                __('translator::translator.tools.json-language')
            );

            Innoclapps::booted(function () {
                SettingsMenu::add('system', SettingsMenuItem::make(
                    __('translator::translator.translator'),
                    '/settings/translator'
                )->setId('translator'));
            });
        });
    }

    /**
     * Register the module commands.
     */
    public function registerCommands(): void
    {
        $this->commands([
            \Modules\Translator\App\Console\Commands\GenerateJsonLanguageFile::class,
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
