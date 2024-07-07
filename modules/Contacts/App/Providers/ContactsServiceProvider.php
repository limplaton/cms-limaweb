<?php
 

namespace Modules\Contacts\App\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Contacts\App\Listeners\AttachEmailAccountMessageToContact;
use Modules\Contacts\App\Listeners\CreateContactFromEmailAccountMessage;
use Modules\Contacts\App\Listeners\TransferContactsUserData;
use Modules\Contacts\App\Models\Company;
use Modules\Contacts\App\Models\Contact;
use Modules\Contacts\App\Observers\CompanyObserver;
use Modules\Contacts\App\Observers\ContactObserver;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Facades\MailableTemplates;
use Modules\Core\App\Facades\Notifications;
use Modules\Core\App\Settings\DefaultSettings;
use Modules\Core\App\Workflow\Workflows;
use Modules\Core\Database\State\DatabaseState;
use Modules\MailClient\App\Events\EmailAccountMessageCreated;
use Modules\Users\App\Events\TransferringUserData;

class ContactsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Contacts';

    protected string $moduleNameLower = 'contacts';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        DatabaseState::register([
            \Modules\Contacts\Database\State\EnsureDefaultFiltersArePresent::class,
            \Modules\Contacts\Database\State\EnsureIndustriesArePresent::class,
            \Modules\Contacts\Database\State\EnsureSourcesArePresent::class,
            \Modules\Contacts\Database\State\EnsureDefaultContactTagsArePresent::class,
        ]);

        DefaultSettings::add('require_calling_prefix_on_phones', true);
        DefaultSettings::add('auto_associate_company_to_contact', 1);

        $this->app['events']->listen(EmailAccountMessageCreated::class, CreateContactFromEmailAccountMessage::class);
        $this->app['events']->listen(EmailAccountMessageCreated::class, AttachEmailAccountMessageToContact::class);
        $this->app['events']->listen(TransferringUserData::class, TransferContactsUserData::class);

        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/migrations'));

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

        Contact::observe(ContactObserver::class);
        Company::observe(CompanyObserver::class);

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
            \Modules\Contacts\App\Resources\Company\Company::class,
            \Modules\Contacts\App\Resources\Contact\Contact::class,
            \Modules\Contacts\App\Resources\Source::class,
            \Modules\Contacts\App\Resources\Industry::class,
        ]);
    }

    /**
     * Register the documents module available notifications.
     */
    public function registerNotifications(): void
    {
        Notifications::register([
            \Modules\Contacts\App\Notifications\UserAssignedToCompany::class,
            \Modules\Contacts\App\Notifications\UserAssignedToContact::class,
        ]);
    }

    /**
     * Register the documents module available mailables.
     */
    public function registerMailables(): void
    {
        MailableTemplates::register([
            \Modules\Contacts\App\Mail\UserAssignedToCompany::class,
            \Modules\Contacts\App\Mail\UserAssignedToContact::class,
        ]);
    }

    /**
     * Register the documents module available workflows.
     */
    public function registerWorkflowTriggers(): void
    {
        Workflows::triggers([
            \Modules\Contacts\App\Workflow\Triggers\CompanyCreated::class,
            \Modules\Contacts\App\Workflow\Triggers\ContactCreated::class,
        ]);
    }

    /**
     * Share data to script.
     */
    public function shareDataToScript(): void
    {
        Innoclapps::provideToScript([
            'contacts' => [
                'tags_type' => Contact::TAGS_TYPE,
            ],
            'contact_fields_height' => settings('contact_fields_height'),
            'company_fields_height' => settings('company_fields_height'),
        ]);
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
