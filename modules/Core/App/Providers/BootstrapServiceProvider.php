<?php
 

namespace Modules\Core\App\Providers;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Modules\Core\App\Macros\RegistersMacros;
use Modules\Core\App\Settings\ConfigOverrides;
use Modules\Core\App\Settings\ConfigRepository;
use Modules\Core\App\Settings\Contracts\Manager as ManagerContract;
use Modules\Core\App\Settings\Contracts\Store as StoreContract;
use Modules\Core\App\Settings\DefaultSettings;
use Modules\Core\App\Settings\SettingsManager;

class BootstrapServiceProvider extends ServiceProvider
{
    use RegistersMacros;

    protected string $moduleName = 'Core';

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Tmp for v1.1.7
        if (is_file(config_path('innoclapps.php'))) {
            $this->deleteConflictedLegacyFiles();
            exit(header('Location: /dashboard'));
        }

        $this->registerSettings();
    }

    /**
     * Boot the service provider.
     */
    public function boot(): void
    {
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/settings.php'),
            'settings'
        );

        ConfigOverrides::add($this->app['config']->get('settings.override', []));

        $this->registerMacros();
        $this->registerDefaultSettings();
        $this->configureDefaultBroadcastConnection();
    }

    /**
     * Register the settings feature.
     */
    protected function registerSettings(): void
    {
        $this->app->singleton(ManagerContract::class, function (Application $app) {
            $manager = new SettingsManager($app);

            foreach ($app['config']->get('settings.drivers', []) as $driver => $params) {
                $manager->registerStore($driver, $params);
            }

            return $manager;
        });

        $this->app->extend('config', function (Repository $repository) {
            return new ConfigRepository($repository->all());
        });

        $this->app->singleton(StoreContract::class, function (Application $app) {
            return $app[ManagerContract::class]->driver();
        });
    }

    /**
     * Register the default settings.
     */
    protected function registerDefaultSettings(): void
    {
        DefaultSettings::addRequired('date_format', 'F j, Y');
        DefaultSettings::addRequired('time_format', 'H:i');
        DefaultSettings::add('block_bad_visitors', false);
        DefaultSettings::addRequired('currency', 'USD');
        DefaultSettings::addRequired(
            'allowed_extensions',
            'jpg, jpeg, png, gif, svg, pdf, aac, ogg, oga, mp3, wav, mp4, m4v,mov, ogv, webm, zip, rar, doc, docx, txt, text, xml, json, xls, xlsx, odt, csv, ppt, pptx, ppsx, ics, eml'
        );
    }

    /**
     * Set the application default broadcast connection.
     */
    protected function configureDefaultBroadcastConnection(): void
    {
        $config = $this->app['config'];

        $keyOptions = Arr::only(
            $config->get('broadcasting.connections.pusher'),
            ['key', 'secret', 'app_id']
        );

        $pusherEnabled = count(array_filter($keyOptions)) === count($keyOptions);

        $pusherOptions = $config->get('broadcasting.connections.pusher.options');

        $config->set('broadcasting.default', $pusherEnabled ? 'pusher' : 'null');

        if ($pusherEnabled && ! str_starts_with($pusherOptions['host'], 'api-'.$pusherOptions['cluster'])) {
            $config->set(
                'broadcasting.connections.pusher.options.host',
                'api-'.$pusherOptions['cluster'].'.pusher.com'
            );
        }
    }

    /**
     * Delete conflicted legacy files.
     */
    protected function deleteConflictedLegacyFiles(): void
    {
        File::delete(config_path('innoclapps.php'));
        File::delete(app_path('Console/Commands/FinalizeUpdateCommand.php'));
        File::delete(app_path('Console/Commands/GenerateJsonLanguageFileCommand.php'));
        File::delete(app_path('Console/Commands/SendScheduledDocuments.php'));
        File::delete(app_path('Console/Commands/ActivitiesNotificationsCommand.php'));
        File::delete(app_path('Console/Commands/UpdateCommand.php'));
        File::delete(config_path('updater.php'));
        File::delete(config_path('settings.php'));
        File::delete(config_path('fields.php'));

        if (is_file(config_path('purifier.php'))) {
            File::delete(config_path('purifier.php'));
        }

        File::delete(config_path('html_purifier.php'));
    }
}
