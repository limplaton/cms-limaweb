<?php
 

namespace Modules\Core\App\Http\View\Composers;

use App\Installer\RequirementsChecker;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Core\App\Facades\Fields;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Facades\Menu;
use Modules\Core\App\Facades\Notifications;
use Modules\Core\App\Facades\ReCaptcha;
use Modules\Core\App\Facades\SettingsMenu;
use Modules\Core\App\Facades\Tools;
use Modules\Core\App\Http\Resources\TagResource;
use Modules\Core\App\Models\Tag;
use Modules\Core\App\Resource\Resource;

class AppComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        Innoclapps::boot();

        /** @var \Modules\Users\App\Models\User */
        $user = Auth::user();

        $config = [];

        $config['apiURL'] = url(\Modules\Core\App\Application::API_PREFIX);
        $config['url'] = url('/');
        $config['locale'] = app()->getLocale();
        $config['locales'] = Innoclapps::locales();
        $config['fallback_locale'] = config('app.fallback_locale');
        $config['timezone'] = config('app.timezone');
        $config['is_secure'] = request()->secure();
        $config['defaults'] = config('core.defaults');
        $config['demo'] = config('demo.enabled');

        if (Innoclapps::requiresMaintenance()) {
            $this->addDataToView($view, $config);

            return;
        }

        $config['broadcasting'] = [
            'default' => config('broadcasting.default'),
            'connection' => config('broadcasting.connections.'.config('broadcasting.default')),
        ];

        // Sensitive settings are not included in this list
        $config['settings'] = [
            'time_format' => config('core.time_format'),
            'date_format' => config('core.date_format'),
            'company_name' => config('app.name'),
            'logo_light' => config('core.logo.light'),
            'logo_dark' => config('core.logo.dark'),
            'disable_password_forgot' => forgot_password_is_disabled(),
        ];

        $config['max_upload_size'] = config('mediable.max_size');
        $config['privacyPolicyUrl'] = privacy_url();

        $config['date_formats'] = config('core.date_formats');
        $config['time_formats'] = config('core.time_formats');

        $config['currency'] = with(Innoclapps::currency(), function ($currency) {
            return array_merge(
                $currency->toArray()[$isoCode = $currency->getCurrency()],
                ['iso_code' => $isoCode]
            );
        });

        $config['reCaptcha'] = [
            'configured' => ReCaptcha::configured(),
            'validate' => ReCaptcha::shouldShow(),
            'siteKey' => ReCaptcha::getSiteKey(),
        ];

        // Required in FormField Group for externals forms e.q. web form
        $config['fields'] = [
            'views' => [
                'index' => Fields::INDEX_VIEW,
                'create' => Fields::CREATE_VIEW,
                'detail' => Fields::DETAIL_VIEW,
                'update' => Fields::UPDATE_VIEW,
            ],
        ];

        // Authenticated user config
        if ($user) {
            $config['version'] = Innoclapps::version();

            if ($user->isSuperAdmin()) {
                $config['purchase_key'] = config('app.purchase_key');
                $config['tools'] = Tools::all();
            }

            $config['resources'] = Innoclapps::registeredResources()->mapWithKeys(
                fn (Resource $resource) => [$resource->name() => $resource]
            );

            $config['tags'] = TagResource::collection(Tag::get());

            $config['fields'] = array_merge($config['fields'], [
                'custom_fields' => Fields::customFieldable(),
                'custom_field_prefix' => config('fields.custom_fields.prefix'),
            ]);

            $config['menu'] = [
                'sidebar' => Menu::get(),
                'metrics' => Menu::metrics(),
                'settings' => SettingsMenu::all(),
            ];

            $config['notifications_settings'] = Notifications::preferences();

            $config['soft_deletes'] = [
                'prune_after' => config('core.soft_deletes.prune_after'),
            ];

            $config['contentbuilder'] = [
                'fonts' => config('contentbuilder.fonts'),
            ];

            $config['integrations'] = [
                'microsoft' => [
                    'client_id' => config('integrations.microsoft.client_id'),
                ],
                'google' => [
                    'client_id' => config('integrations.google.client_id'),
                ],
            ];

            $config['favourite_colors'] = Innoclapps::favouriteColors();

            $requirements = app(RequirementsChecker::class);

            $config['requirements'] = [
                'imap' => $requirements->passes('imap'),
                'zip' => $requirements->passes('zip'),
            ];
        }

        $this->addDataToView($view, array_merge_recursive($config, Innoclapps::getDataProvidedToScript()));
    }

    /**
     * Add data to the given view
     */
    protected function addDataToView(View $view, array $config): void
    {
        $lang = get_generated_lang(app()->getLocale());

        $view->with(['config' => $config, 'lang' => $lang]);
    }
}
