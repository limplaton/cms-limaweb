<?php
 

namespace Modules\Translator\App\Providers;

use Illuminate\Foundation\Application;
use Illuminate\Translation\TranslationServiceProvider as BaseTranslationServiceProvider;
use Modules\Translator\App\Contracts\TranslationLoader;
use Modules\Translator\App\LoaderManager;
use Modules\Translator\App\Loaders\OverrideFileLoader;

class TranslationServiceProvider extends BaseTranslationServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        parent::register();

        $this->app->bind(TranslationLoader::class, function (Application $app) {
            return new OverrideFileLoader($app['config']->get('translator.custom'));
        });
    }

    /**
     * Register the translation line loader. This method registers a
     * `LoaderManager` instead of a simple `FileLoader` as the
     * applications `translation.loader` instance.
     */
    protected function registerLoader(): void
    {
        $this->app->singleton('translation.loader', function (Application $app) {
            return new LoaderManager($app['files'], $app['path.lang']);
        });
    }
}
