<?php
 

namespace Modules\Deals\App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Facades\MailableTemplates;
use Modules\Core\App\Facades\Menu;
use Modules\Core\App\Facades\Notifications;
use Modules\Core\App\Settings\DefaultSettings;
use Modules\Core\App\Workflow\Workflows;
use Modules\Core\Database\State\DatabaseState;
use Modules\Deals\App\Events\DealMovedToStage;
use Modules\Deals\App\Http\Resources\LostReasonResource;
use Modules\Deals\App\Http\Resources\PipelineResource;
use Modules\Deals\App\Listeners\LogDealMovedToStageActivity;
use Modules\Deals\App\Listeners\TransferDealsUserData;
use Modules\Deals\App\Menu\OpenDealsMetric;
use Modules\Deals\App\Models\Deal;
use Modules\Deals\App\Models\LostReason;
use Modules\Deals\App\Models\Pipeline;
use Modules\Deals\App\Observers\DealObserver;
use Modules\Users\App\Events\TransferringUserData;

class DealsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Deals';

    protected string $moduleNameLower = 'deals';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/migrations'));

        $this->app['events']->listen(DealMovedToStage::class, LogDealMovedToStageActivity::class);
        $this->app['events']->listen(TransferringUserData::class, TransferDealsUserData::class);

        DatabaseState::register([
            \Modules\Deals\Database\State\EnsureDefaultFiltersArePresent::class,
            \Modules\Deals\Database\State\EnsureDefaultPipelineIsPresent::class,
        ]);

        DefaultSettings::add('allow_lost_reason_enter', true);
        DefaultSettings::add('lost_reason_is_required', true);

        Menu::metric(new OpenDealsMetric);

        $this->registerNotifications();
        $this->registerMailables();
        $this->registerWorkflowTriggers();

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
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Boot the module.
     */
    protected function bootModule(): void
    {
        $this->registerResources();
        Deal::observe(DealObserver::class);

        Innoclapps::whenReadyForServing(function () {
            Innoclapps::booted($this->shareDataToScript(...));
        });
    }

    /**
     * Register the documents module available resources.
     */
    public function registerResources(): void
    {
        Innoclapps::resources([
            \Modules\Deals\App\Resources\Deal::class,
            \Modules\Deals\App\Resources\Pipeline::class,
            \Modules\Deals\App\Resources\PipelineStage::class,
            \Modules\Deals\App\Resources\LostReason::class,
        ]);
    }

    /**
     * Register the documents module available notifications.
     */
    public function registerNotifications(): void
    {
        Notifications::register([
            \Modules\Deals\App\Notifications\UserAssignedToDeal::class,
        ]);
    }

    /**
     * Register the documents module available mailables.
     */
    public function registerMailables(): void
    {
        MailableTemplates::register([
            \Modules\Deals\App\Mail\UserAssignedToDeal::class,
        ]);
    }

    /**
     * Register the documents module available workflows.
     */
    public function registerWorkflowTriggers(): void
    {
        Workflows::triggers([
            \Modules\Deals\App\Workflow\Triggers\DealCreated::class,
            \Modules\Deals\App\Workflow\Triggers\DealStageChanged::class,
            \Modules\Deals\App\Workflow\Triggers\DealStatusChanged::class,
        ]);
    }

    /**
     * Share data to script.
     */
    public function shareDataToScript(): void
    {
        if (Auth::check()) {
            Innoclapps::provideToScript([
                'settings' => [
                    'allow_lost_reason_enter' => settings('allow_lost_reason_enter'),
                    'lost_reason_is_required' => settings('lost_reason_is_required'),
                ],

                'deal_fields_height' => settings('deal_fields_height'),

                'deals' => [
                    'tags_type' => Deal::TAGS_TYPE,
                    'pipelines' => PipelineResource::collection(
                        Pipeline::withCommon()
                            ->with('stages')
                            ->withVisibilityGroups()
                            ->visible()
                            ->userOrdered()
                            ->get()
                    ),
                    'lost_reasons' => LostReasonResource::collection(
                        LostReason::withCommon()->orderBy('name')->get()
                    ),
                ],
            ]);
        }
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
