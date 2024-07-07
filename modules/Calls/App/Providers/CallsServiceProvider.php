<?php
 

namespace Modules\Calls\App\Providers;

use App\Http\View\FrontendComposers\Tab;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Modules\Calls\App\Http\Resources\CallOutcomeResource;
use Modules\Calls\App\Listeners\TransferCallsUserData;
use Modules\Calls\App\Models\CallOutcome;
use Modules\Calls\App\VoIP\VoIP;
use Modules\Contacts\App\Fields\Phone;
use Modules\Contacts\App\Resources\Company\Pages\DetailComponent as CompanyDetailComponent;
use Modules\Contacts\App\Resources\Contact\Pages\DetailComponent as ContactDetailComponent;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Facades\Permissions;
use Modules\Core\App\Facades\SettingsMenu;
use Modules\Core\App\Settings\ConfigOverrides;
use Modules\Core\App\Settings\SettingsMenuItem;
use Modules\Core\App\Workflow\Workflows;
use Modules\Core\Database\State\DatabaseState;
use Modules\Deals\App\Resources\Pages\DetailComponent as DealDetailComponent;
use Modules\Users\App\Events\TransferringUserData;

class CallsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Calls';

    protected string $moduleNameLower = 'calls';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/migrations'));

        DatabaseState::register(\Modules\Calls\Database\State\EnsureCallOutcomesArePresent::class);

        $this->app['events']->listen(TransferringUserData::class, TransferCallsUserData::class);

        Phone::useDetailComponent('detail-phone-callable-field');
        Phone::useIndexComponent('index-phone-callable-field');

        ConfigOverrides::add([
            'twilio.applicationSid' => 'twilio_app_sid',
            'twilio.accountSid' => 'twilio_account_sid',
            'twilio.authToken' => 'twilio_auth_token',
            'twilio.number' => 'twilio_number',
        ]);

        $this->registerWorkflowTriggers();
        $this->registerRelatedRecordsDetailTab();

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

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/twilio.php'),
            'twilio'
        );

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/voip.php'),
            'voip'
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

        Innoclapps::whenReadyForServing(function () {
            $this->configureVoIP();

            Innoclapps::booted(function () {
                $this->shareDataToScript();
                SettingsMenu::add(
                    'integrations', SettingsMenuItem::make('Twilio', '/settings/integrations/twilio')->setId('twilio')
                );
            });
        });

    }

    /**
     * Set the application VoIP Client
     */
    protected function configureVoIP(): void
    {
        $options = $this->app['config']->get('twilio');

        $totalFilled = count(array_filter($options));

        if ($totalFilled === count($options)) {
            $this->app['config']->set('voip.client', 'twilio');

            Permissions::register(function ($manager) {
                $manager->group(['name' => 'voip', 'as' => __('calls::call.voip_permissions')], function ($manager) {
                    $manager->view('view', [
                        'as' => __('calls::call.capabilities.use_voip'),
                        'permissions' => ['use voip' => __('calls::call.capabilities.use_voip')],
                    ]);
                });
            });
        }
    }

    /**
     * Register the module available resources.
     */
    public function registerResources(): void
    {
        Innoclapps::resources([
            \Modules\Calls\App\Resources\Call::class,
            \Modules\Calls\App\Resources\CallOutcome::class,
        ]);
    }

    /**
     * Register the module workflow triggers.
     */
    protected function registerWorkflowTriggers(): void
    {
        Workflows::triggers([
            \Modules\Calls\App\Workflow\Triggers\MissedIncomingCall::class,
        ]);
    }

    /**
     * Share data to script.
     */
    protected function shareDataToScript(): void
    {
        if (! Auth::check()) {
            return;
        }

        Innoclapps::provideToScript([
            'voip' => [
                'client' => config('voip.client'),
                'endpoints' => [
                    'call' => VoIP::callUrl(),
                    'events' => VoIP::eventsUrl(),
                ],
            ],

            'calls' => [
                'outcomes' => CallOutcomeResource::collection(CallOutcome::orderBy('name')->get()),
            ],
        ]);
    }

    /**
     * Register the module related tabs.
     */
    public function registerRelatedRecordsDetailTab(): void
    {
        $tab = Tab::make('calls', 'calls-tab')->panel('calls-tab-panel')->order(30);

        ContactDetailComponent::registerTab($tab);
        CompanyDetailComponent::registerTab($tab);
        DealDetailComponent::registerTab($tab);
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
