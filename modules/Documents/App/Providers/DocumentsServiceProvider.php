<?php
 

namespace Modules\Documents\App\Providers;

use App\Http\View\FrontendComposers\Tab;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Modules\Contacts\App\Resources\Company\Pages\DetailComponent as CompanyDetailComponent;
use Modules\Contacts\App\Resources\Contact\Pages\DetailComponent as ContactDetailComponent;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Facades\MailableTemplates;
use Modules\Core\App\Facades\Notifications;
use Modules\Core\App\Settings\DefaultSettings;
use Modules\Core\App\Workflow\Workflows;
use Modules\Core\Database\State\DatabaseState;
use Modules\Deals\App\Resources\Pages\DetailComponent as DealDetailComponent;
use Modules\Documents\App\Console\Commands\SendScheduledDocuments;
use Modules\Documents\App\Content\DocumentContent;
use Modules\Documents\App\Enums\DocumentStatus;
use Modules\Documents\App\Http\Resources\DocumentTypeResource;
use Modules\Documents\App\Listeners\TransferDocumentsUserData;
use Modules\Documents\App\Models\Document;
use Modules\Documents\App\Models\DocumentType;
use Modules\Documents\App\Observers\DocumentObserver;
use Modules\Users\App\Events\TransferringUserData;

class DocumentsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Documents';

    protected string $moduleNameLower = 'documents';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/migrations'));

        DatabaseState::register(\Modules\Documents\Database\State\EnsureDocumentTypesArePresent::class);

        DefaultSettings::addRequired('default_document_type');

        $this->app['events']->listen(TransferringUserData::class, TransferDocumentsUserData::class);

        $this->commands([
            SendScheduledDocuments::class,
        ]);

        $this->registerNotifications();
        $this->registerMailables();
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
     * Boot the module.
     */
    protected function bootModule(): void
    {
        $this->registerResources();
        Document::observe(DocumentObserver::class);

        Innoclapps::whenReadyForServing(function () {
            Innoclapps::booted($this->shareDataToScript(...));
            $this->scheduleTasks();
        });
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
     * Register the documents module available resources.
     */
    public function registerResources(): void
    {
        Innoclapps::resources([
            \Modules\Documents\App\Resources\Document::class,
            \Modules\Documents\App\Resources\DocumentType::class,
            \Modules\Documents\App\Resources\DocumentTemplate::class,
        ]);
    }

    /**
     * Register the documents module available notifications.
     */
    public function registerNotifications(): void
    {
        Notifications::register([
            \Modules\Documents\App\Notifications\DocumentAccepted::class,
            \Modules\Documents\App\Notifications\DocumentViewed::class,
            \Modules\Documents\App\Notifications\SignerSignedDocument::class,
            \Modules\Documents\App\Notifications\UserAssignedToDocument::class,
        ]);
    }

    /**
     * Register the documents module available mailables.
     */
    public function registerMailables(): void
    {
        MailableTemplates::register([
            \Modules\Documents\App\Mail\DocumentAccepted::class,
            \Modules\Documents\App\Mail\DocumentViewed::class,
            \Modules\Documents\App\Mail\SignerSignedDocument::class,
            \Modules\Documents\App\Mail\UserAssignedToDocument::class,
        ]);
    }

    /**
     * Register the documents module available workflows.
     */
    public function registerWorkflowTriggers(): void
    {
        Workflows::triggers([
            \Modules\Documents\App\Workflow\Triggers\DocumentStatusChanged::class,
        ]);
    }

    /**
     * Register the documents module related tabs.
     */
    public function registerRelatedRecordsDetailTab(): void
    {
        $tab = Tab::make('documents', 'documents-tab')->panel('documents-tab-panel')->order(25);

        ContactDetailComponent::registerTab($tab);
        CompanyDetailComponent::registerTab($tab);
        DealDetailComponent::registerTab($tab);
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $this->loadTranslationsFrom(module_path($this->moduleName, 'lang'), $this->moduleNameLower);
    }

    /**
     * Schedule the document related tasks.
     */
    public function scheduleTasks(): void
    {
        /** @var \Illuminate\Console\Scheduling\Schedule */
        $schedule = $this->app->make(Schedule::class);

        $schedule->safeCommand('documents:send-scheduled')
            ->name('send-scheduled-documents')
            ->everyTwoMinutes()
            ->withoutOverlapping(5);
    }

    /**
     * Share data to script.
     */
    public function shareDataToScript(): void
    {
        if (Auth::check()) {
            Innoclapps::provideToScript([
                'documents' => [
                    'default_document_type' => DocumentType::getDefaultType(),

                    'navigation_heading_tag_name' => DocumentContent::NAVIGATION_HEADING_TAG_NAME,

                    'placeholders' => (new Document)->placeholders(),

                    'statuses' => collect(DocumentStatus::cases())->mapWithKeys(
                        function (DocumentStatus $case) {
                            return [
                                $case->value => [
                                    'name' => $case->value,
                                    'icon' => $case->icon(),
                                    'color' => $case->color(),
                                    'display_name' => $case->displayName(),
                                ],
                            ];
                        }
                    ),

                    'types' => DocumentTypeResource::collection(
                        DocumentType::withCommon()
                            ->withVisibilityGroups()
                            ->visible()
                            ->orderBy('name')
                            ->get()
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
