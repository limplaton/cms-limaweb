<?php
 

namespace Modules\MailClient\App\Providers;

use App\Http\View\FrontendComposers\Tab;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Modules\Contacts\App\Resources\Company\Pages\DetailComponent as CompanyDetailComponent;
use Modules\Contacts\App\Resources\Contact\Pages\DetailComponent as ContactDetailComponent;
use Modules\Core\App\Common\OAuth\Events\OAuthAccountConnected;
use Modules\Core\App\Common\OAuth\Events\OAuthAccountDeleting;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Facades\Menu;
use Modules\Core\App\Facades\Permissions;
use Modules\Core\App\Fields\Email;
use Modules\Core\App\Menu\MenuItem;
use Modules\Core\App\SystemInfo;
use Modules\Deals\App\Resources\Pages\DetailComponent as DealDetailComponent;
use Modules\MailClient\App\Client\ClientManager;
use Modules\MailClient\App\Client\ConnectionType;
use Modules\MailClient\App\Client\FolderType;
use Modules\MailClient\App\Console\Commands\PruneStaleScheduledEmails;
use Modules\MailClient\App\Console\Commands\SendScheduledEmails;
use Modules\MailClient\App\Console\Commands\SyncEmailAccounts;
use Modules\MailClient\App\Criteria\EmailAccountsForUserCriteria;
use Modules\MailClient\App\Listeners\CreateEmailAccountViaOAuth;
use Modules\MailClient\App\Listeners\StopRelatedOAuthEmailAccounts;
use Modules\MailClient\App\Listeners\TransferMailClientUserData;
use Modules\MailClient\App\Models\EmailAccount;
use Modules\MailClient\App\Models\EmailAccountMessage;
use Modules\Users\App\Events\TransferringUserData;

class MailClientServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'MailClient';

    protected string $moduleNameLower = 'mailclient';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/migrations'));

        $this->registerPermissions();

        Email::useDetailComponent('detail-email-sendable-field');
        Email::useIndexComponent('index-email-sendable-field');

        $this->app['events']->listen(OAuthAccountConnected::class, CreateEmailAccountViaOAuth::class);
        $this->app['events']->listen(OAuthAccountDeleting::class, StopRelatedOAuthEmailAccounts::class);
        $this->app['events']->listen(TransferringUserData::class, TransferMailClientUserData::class);

        $this->commands([
            SyncEmailAccounts::class,
            SendScheduledEmails::class,
            PruneStaleScheduledEmails::class,
        ]);

        SystemInfo::register('MAIL_CLIENT_SYNC_INTERVAL', $this->app['config']->get('mailclient.sync.interval'));

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
     * Boot the mail client module.
     */
    protected function bootModule(): void
    {
        $this->registerResources();

        Innoclapps::whenReadyForServing(function () {
            Innoclapps::booted($this->registerMenuItems(...));
            Innoclapps::booted($this->shareDataToScript(...));
            $this->scheduleTasks();
        });
    }

    /**
     * Schedule the document related tasks.
     */
    public function scheduleTasks(): void
    {
        /** @var \Illuminate\Console\Scheduling\Schedule */
        $schedule = $this->app->make(Schedule::class);

        $schedule->safeCommand('mailclient:sync', ['--broadcast', '--isolated' => 5])
            ->cron($this->app['config']->get('mailclient.sync.interval'))
            ->name('sync-email-accounts')
            ->withoutOverlapping(30)
            ->sendOutputTo(storage_path('logs/email-accounts-sync.log'));

        $schedule->safeCommand('mailclient:prune-failed')
            ->daily()
            ->name('prune-failed-scheduled-emails');

        $schedule->safeCommand('mailclient:send-scheduled')
            ->everyThreeMinutes()
            ->name('send-scheduled-emails');
    }

    /**
     * Register the mail client module resources.
     */
    public function registerResources(): void
    {
        Innoclapps::resources([
            \Modules\MailClient\App\Resources\EmailMessage::class,
        ]);
    }

    /**
     * Share data to script.
     */
    protected function shareDataToScript(): void
    {
        Innoclapps::provideToScript(['mail' => [
            'tags_type' => EmailAccountMessage::TAGS_TYPE,
            'reply_prefix' => config('mailclient.reply_prefix'),
            'forward_prefix' => config('mailclient.forward_prefix'),
            'accounts' => [
                'connections' => ConnectionType::cases(),
                'encryptions' => ClientManager::ENCRYPTION_TYPES,
                'from_name' => EmailAccount::DEFAULT_FROM_NAME_HEADER,
            ],
            'folders' => [
                'outgoing' => FolderType::outgoingTypes(),
                'incoming' => FolderType::incomingTypes(),
                'other' => FolderType::OTHER,
                'drafts' => FolderType::DRAFTS,
            ],
        ],
        ]);
    }

    /**
     * Register the menu items.
     */
    private function registerMenuItems(): void
    {
        $accounts = auth()->check() ? EmailAccount::with('oAuthAccount')
            ->criteria(EmailAccountsForUserCriteria::class)
            ->get()->filter->canSendEmail() : null;

        Menu::register(
            MenuItem::make(__('mailclient::inbox.inbox'), '/inbox', 'Inbox')
                ->position(15)
                ->badge(fn () => EmailAccount::countUnreadMessagesForUser(Auth::user()))
                ->inQuickCreate(! is_null($accounts?->filter->isPrimary()->first() ?? $accounts?->first()))
                ->quickCreateName(__('mailclient::mail.send'))
                ->quickCreateRoute('/inbox?compose=true')
                ->keyboardShortcutChar('E')
                ->badgeVariant('info')
        );
    }

    /**
     * Register the mail client module permissions.
     */
    protected function registerPermissions(): void
    {
        Permissions::register(function ($manager) {
            $manager->group(['name' => 'inbox', 'as' => __('mailclient::inbox.shared')], function ($manager) {
                $manager->view('access-inbox', [
                    'as' => __('core::role.capabilities.access'),
                    'permissions' => [
                        'access shared inbox' => __('core::role.capabilities.access'),
                    ],
                ]);
            });
        });
    }

    /**
     * Register the documents module related tabs.
     */
    public function registerRelatedRecordsDetailTab(): void
    {
        $tab = Tab::make('emails', 'emails-tab')->panel('emails-tab-panel')->order(20);

        ContactDetailComponent::registerTab($tab);
        CompanyDetailComponent::registerTab($tab);
        DealDetailComponent::registerTab($tab);
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
