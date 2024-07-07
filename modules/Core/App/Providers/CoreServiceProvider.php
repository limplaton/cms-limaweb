<?php
 

namespace Modules\Core\App\Providers;

use App\Installer\Events\InstallationSucceeded;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Migrations\Migrator as LaravelMigrator;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Core\App\Common\Media\PruneStaleMediaAttachments;
use Modules\Core\App\Common\Synchronization\Jobs\PeriodicSynchronizations;
use Modules\Core\App\Common\Synchronization\Jobs\RefreshWebhookSynchronizations;
use Modules\Core\App\Common\Timeline\Timelineables;
use Modules\Core\App\Database\Migrator;
use Modules\Core\App\Environment;
use Modules\Core\App\Facades\ChangeLogger;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Facades\MailableTemplates;
use Modules\Core\App\Facades\Menu;
use Modules\Core\App\Facades\SettingsMenu;
use Modules\Core\App\Facades\Tools;
use Modules\Core\App\Facades\Zapier;
use Modules\Core\App\Menu\MenuItem;
use Modules\Core\App\Resource\Resource;
use Modules\Core\App\Settings\SettingsMenuItem;
use Modules\Core\App\Updater\Events\PatchApplied;
use Modules\Core\App\Updater\Events\UpdateFinalized;
use Modules\Core\App\Workflow\WorkflowEventsSubscriber;
use Modules\Core\App\Workflow\Workflows;
use Modules\Core\Database\State\DatabaseState;

class CoreServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Core';

    protected string $moduleNameLower = 'core';

    protected array $configs = ['html_purifier', 'fields', 'updater', 'synchronization', 'integrations'];

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/migrations'));

        $this->app['events']->listen(RequestHandled::class, Workflows::processQueue(...));
        $this->app['events']->listen(RequestHandled::class, Zapier::processQueue(...));

        $this->app['events']->listen(
            [PatchApplied::class, UpdateFinalized::class],
            MailableTemplates::seed(...)
        );

        $this->app['events']->listen(InstallationSucceeded::class, function () {
            ChangeLogger::disabled(fn () => DatabaseState::seed());
        });

        Innoclapps::whenReadyForServing(Timelineables::discover(...));
        Innoclapps::booting($this->registerMenuItems(...));
        Innoclapps::booting($this->registerSettingsMenuItems(...));

        View::composer(
            ['core::app', 'core::components/layouts/skin'],
            \Modules\Core\App\Http\View\Composers\AppComposer::class
        );

        $this->registerCommands();

        $this->app->booted($this->listenToWorkflowEvents(...));
        $this->app->booted($this->scheduleTasks(...));
        $this->app->booted($this->registerTools(...));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        DatabaseState::register([
            \Modules\Core\Database\State\EnsureMailableTemplatesAreSeeded::class,
            \Modules\Core\Database\State\EnsureDefaultSettingsArePresent::class,
            \Modules\Core\Database\State\EnsureCountriesArePresent::class,
        ]);

        $this->app->singleton('timezone', \Modules\Core\App\Timezone::class);

        $this->app->when(Migrator::class)->needs(LaravelMigrator::class)->give(fn () => $this->app['migrator']);

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

        foreach ($this->configs as $config) {
            $this->mergeConfigFrom(
                module_path($this->moduleName, "config/$config.php"),
                $config
            );
        }

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
     * Register the menu items.
     */
    protected function registerMenuItems(): void
    {
        Menu::register(MenuItem::make(__('core::dashboard.insights'), '/dashboard', 'ChartSquareBar')
            ->position(40));

        Menu::register(MenuItem::make(__('core::settings.settings'), '/settings', 'Cog')
            ->canSeeWhen('is-super-admin')
            ->position(100));
    }

    /**
     * Register the core settings menu items.
     */
    protected function registerSettingsMenuItems(): void
    {
        SettingsMenu::register(
            SettingsMenuItem::make(__('core::app.integrations'))->icon('Globe')->order(20)
                ->withChild(SettingsMenuItem::make('Pusher', '/settings/integrations/pusher'), 'pusher')
                ->withChild(SettingsMenuItem::make('Microsoft', '/settings/integrations/microsoft'), 'microsoft')
                ->withChild(SettingsMenuItem::make('Google', '/settings/integrations/google'), 'google')
                ->withChild(SettingsMenuItem::make('Zapier', '/settings/integrations/zapier'), 'zapier'),
            'integrations'
        );

        SettingsMenu::register(
            SettingsMenuItem::make(__('core::settings.security.security'))->icon('ShieldCheck')->order(60)
                ->withChild(SettingsMenuItem::make(__('core::settings.general'), '/settings/security'), 'security')
                ->withChild(SettingsMenuItem::make(__('core::settings.recaptcha.recaptcha'), '/settings/recaptcha'), 'recaptcha'),
            'security'
        );

        SettingsMenu::register(
            SettingsMenuItem::make(__('core::settings.system'))->icon('Cog')->order(70)
                ->withChild(SettingsMenuItem::make(__('core::update.update'), '/settings/update'), 'update')
                ->withChild(SettingsMenuItem::make(__('core::settings.tools.tools'), '/settings/tools'), 'tools')
                ->withChild(SettingsMenuItem::make(__('core::app.system_info'), '/settings/info'), 'system-info')
                ->withChild(SettingsMenuItem::make('Logs', '/settings/logs'), 'system-logs'),
            'system'
        );

        SettingsMenu::register(
            SettingsMenuItem::make(__('core::workflow.workflows'), '/settings/workflows', 'RocketLaunch')->order(40),
            'workflows'
        );

        SettingsMenu::register(
            SettingsMenuItem::make(__('core::mail_template.mail_templates'), '/settings/mailable-templates', 'Mail')->order(50),
            'mailable-templates'
        );

        tap(SettingsMenuItem::make(__('core::fields.fields'))->icon('SquaresPlus')->order(10), function ($item) {
            Innoclapps::registeredResources()
                ->filter(fn ($resource) => $resource::$fieldsCustomizable)
                ->each(function (Resource $resource) use ($item) {
                    $item->withChild(
                        SettingsMenuItem::make(
                            $resource->singularLabel(),
                            "/settings/fields/{$resource->name()}"
                        ),
                        'fields-'.$resource->name()
                    );
                });
            SettingsMenu::register($item, 'fields');
        });
    }

    /**
     * Register the core commands.
     */
    public function registerCommands(): void
    {
        $this->commands([
            \Modules\Core\App\Console\Commands\OptimizeCommand::class,
            \Modules\Core\App\Console\Commands\ClearCacheCommand::class,
            \Modules\Core\App\Console\Commands\ClearExcelTmpPathCommand::class,
            \Modules\Core\App\Console\Commands\ClearHtmlPurifierCacheCommand::class,
            \Modules\Core\App\Console\Commands\UpdateCommand::class,
            \Modules\Core\App\Console\Commands\PatchCommand::class,
            \Modules\Core\App\Console\Commands\FinalizeUpdateCommand::class,
            \Modules\Core\App\Console\Commands\GenerateIdentificationKeyCommand::class,
        ]);
    }

    /**
     * Schedule the document related tasks.
     */
    public function scheduleTasks(): void
    {
        /** @var \Illuminate\Console\Scheduling\Schedule */
        $schedule = $this->app->make(Schedule::class);

        $schedule->call(Environment::captureCron(...))->name('capture-cron-environment')->everyMinute();
        $schedule->call(new PruneStaleMediaAttachments)->name('prune-stale-media-attachments')->daily();
        $schedule->job(PeriodicSynchronizations::class)->cron(config('synchronization.interval'));
        $schedule->job(RefreshWebhookSynchronizations::class)->daily();

        $schedule->safeCommand('model:prune')->daily();
        $schedule->safeCommand('queue:flush')->weekly();
        $schedule->safeCommand('updater:patch')->twiceDaily()->when(function () {
            return (bool) config('updater.auto_patch');
        });
    }

    /**
     * Listen to workflow events.
     */
    protected function listenToWorkflowEvents(): void
    {
        // Must be called before registering the "WorkflowEventsSubscriber" subscriber.
        Workflows::registerEventOnlyTriggersListeners();
        $this->app['events']->subscribe(WorkflowEventsSubscriber::class);
    }

    /**
     * Register the core tools.
     */
    protected function registerTools(): void
    {
        Tools::register('clear-cache', function () {
            Innoclapps::clearCache();
            Innoclapps::restartQueue();
        }, __('core::settings.tools.clear-cache'));

        Tools::register('optimize', function () {
            Innoclapps::optimize();
            Innoclapps::restartQueue();
        }, __('core::settings.tools.optimize'));

        Tools::register('storage-link', Innoclapps::createStorageLink(...), __('core::settings.tools.storage-link'));
        Tools::register('seed-mailable-templates', MailableTemplates::seed(...), __('core::settings.tools.seed-mailable-templates'));
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
