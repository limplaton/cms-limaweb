<?php
 

namespace Modules\Activities\App\Providers;

use App\Http\View\FrontendComposers\Tab;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Modules\Activities\App\Console\Commands\SendActivitiesNotifications;
use Modules\Activities\App\Console\Commands\SyncNextActivityDate;
use Modules\Activities\App\Http\Resources\ActivityTypeResource;
use Modules\Activities\App\Listeners\StopRelatedOAuthCalendars;
use Modules\Activities\App\Listeners\TransferActivitiesUserData;
use Modules\Activities\App\Menu\TodaysActivitiesMetric;
use Modules\Activities\App\Models\Activity;
use Modules\Activities\App\Models\ActivityType;
use Modules\Activities\App\Observers\ActivityObserver;
use Modules\Activities\App\Observers\ActivityTransactionAwareObserver;
use Modules\Contacts\App\Resources\Company\Pages\DetailComponent as CompanyDetailComponent;
use Modules\Contacts\App\Resources\Contact\Pages\DetailComponent as ContactDetailComponent;
use Modules\Core\App\Common\OAuth\Events\OAuthAccountDeleting;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Facades\MailableTemplates;
use Modules\Core\App\Facades\Menu;
use Modules\Core\App\Facades\Notifications;
use Modules\Core\App\Settings\DefaultSettings;
use Modules\Core\App\SystemInfo;
use Modules\Core\Database\State\DatabaseState;
use Modules\Deals\App\Resources\Pages\DetailComponent as DealDetailComponent;
use Modules\Users\App\Events\TransferringUserData;

class ActivitiesServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Activities';

    protected string $moduleNameLower = 'activities';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/migrations'));

        DatabaseState::register([
            \Modules\Activities\Database\State\EnsureDefaultFiltersArePresent::class,
            \Modules\Activities\Database\State\EnsureActivityTypesArePresent::class,
        ]);

        $this->app['events']->listen(OAuthAccountDeleting::class, StopRelatedOAuthCalendars::class);
        $this->app['events']->listen(TransferringUserData::class, TransferActivitiesUserData::class);

        DefaultSettings::add('send_contact_attends_to_activity_mail', false);
        DefaultSettings::addRequired('default_activity_type');

        $this->commands([
            SendActivitiesNotifications::class,
            SyncNextActivityDate::class,
        ]);

        SystemInfo::register('PREFERRED_DEFAULT_HOUR', $this->app['config']->get('activities.defaults.hour'));
        SystemInfo::register('PREFERRED_DEFAULT_MINUTES', $this->app['config']->get('activities.defaults.minutes'));

        $this->registerNotifications();
        $this->registerMailables();
        $this->registerRelatedRecordsDetailTab();
        Menu::metric(new TodaysActivitiesMetric);

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
        $this->registerResources();

        Activity::observe(ActivityObserver::class);
        Activity::observe(ActivityTransactionAwareObserver::class);

        Innoclapps::whenReadyForServing(function () {
            Innoclapps::booted($this->shareDataToScript(...));

            $this->scheduleTasks();
        });
    }

    /**
     * Schedule the module tasks.
     */
    protected function scheduleTasks(): void
    {
        /** @var \Illuminate\Console\Scheduling\Schedule */
        $schedule = $this->app->make(Schedule::class);

        $schedule->safeCommand('activities:notify')
            ->name('notify-due-activities')
            ->everyMinute()
            ->withoutOverlapping(5);

        $schedule->safeCommand('activities:sync-next-date')
            ->name('sync-next-activity')
            ->everyFiveMinutes()
            ->withoutOverlapping(5);
    }

    /**
     * Register the module available resources.
     */
    protected function registerResources(): void
    {
        Innoclapps::resources([
            \Modules\Activities\App\Resources\Activity::class,
            \Modules\Activities\App\Resources\ActivityType::class,
        ]);
    }

    /**
     * Register the activities module available notifications.
     */
    protected function registerNotifications(): void
    {
        Notifications::register([
            \Modules\Activities\App\Notifications\ActivityReminder::class,
            \Modules\Activities\App\Notifications\UserAssignedToActivity::class,
            \Modules\Activities\App\Notifications\UserAttendsToActivity::class,
        ]);
    }

    /**
     * Register the module available mailables.
     */
    protected function registerMailables(): void
    {
        MailableTemplates::register([
            \Modules\Activities\App\Mail\ActivityReminder::class,
            \Modules\Activities\App\Mail\ContactAttendsToActivity::class,
            \Modules\Activities\App\Mail\UserAssignedToActivity::class,
            \Modules\Activities\App\Mail\UserAttendsToActivity::class,
        ]);
    }

    /**
     * Register the module related tabs.
     */
    protected function registerRelatedRecordsDetailTab(): void
    {
        $tab = Tab::make('activities', 'activities-tab')->panel('activities-tab-panel')->order(15);

        ContactDetailComponent::registerTab($tab);
        CompanyDetailComponent::registerTab($tab);
        DealDetailComponent::registerTab($tab);
    }

    /**
     * Share data to script.
     */
    protected function shareDataToScript(): void
    {
        if (Auth::check()) {
            Innoclapps::provideToScript([
                'activities' => [
                    'defaults' => config('activities.defaults'),
                    'default_activity_type_id' => ActivityType::getDefaultType(),

                    'types' => ActivityTypeResource::collection(
                        ActivityType::withCommon()->orderBy('name')->get()
                    ),
                ],
            ]);
        }
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
