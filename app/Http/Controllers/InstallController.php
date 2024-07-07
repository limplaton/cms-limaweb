<?php
 

namespace App\Http\Controllers;

use Akaunting\Money\Currency;
use App\Installer\DatabaseTest;
use App\Installer\Environment;
use App\Installer\EnvironmentManager;
use App\Installer\FailedToFinalizeInstallationException;
use App\Installer\FinishesInstallation;
use App\Installer\PermissionsChecker;
use App\Installer\PrivilegeNotGrantedException;
use App\Installer\PrivilegesChecker;
use App\Installer\RequirementsChecker;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Connection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View as ViewFacade;
use Modules\Core\App\Models\Country;
use Modules\Core\App\Rules\StringRule;
use Modules\Core\App\Settings\DefaultSettings;
use Modules\Core\App\Updater\Exceptions\UpdaterException;
use Modules\Core\App\Updater\Patcher;
use Modules\Users\App\Models\User;

class InstallController extends Controller
{
    use FinishesInstallation;

    const ENV_WRITE_FAILED_MESSAGE = 'Failed to write .env file, make sure that the files permissions and ownership are correct. Check documentation on how to setup the permissions and ownership.';

    /**
     * Shows the requirements page.
     */
    public function index(RequirementsChecker $checker, EnvironmentManager $environment): View
    {
        $step = 1;
        $requirements = $checker->check();
        $php = $checker->checkPHPversion();

        ViewFacade::share(['step' => $step]);

        return view('installer.requirements', compact(
            'php',
            'requirements',
        ));
    }

    /**
     * Shows the permissions page.
     */
    public function permissions(PermissionsChecker $checker): View
    {
        $step = 2;
        $permissions = $checker->check();

        ViewFacade::share(['step' => $step]);

        return view('installer.permissions', compact('permissions'));
    }

    /**
     * Application setup.
     */
    public function setup(EnvironmentManager $environmentManager): View
    {
        $step = 3;

        $guessedUrl = $environmentManager::guessUrl();

        $countries = Country::list();
        $currencies = Currency::getCurrencies();

        ViewFacade::share(['step' => $step]);

        return view('installer.setup', compact(
            'guessedUrl',
            'countries',
            'currencies'
        ));
    }

    /**
     * Store the environmental variables.
     */
    public function setupStore(Request $request, EnvironmentManager $environmentManager): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'app_url' => 'required|url',
            'app_name' => 'required',
            'currency' => 'required',
            'country' => 'required',
            'database_hostname' => 'required',
            'database_port' => 'required',
            'database_name' => 'required',
            'database_username' => 'required',
            // Allow blank for local installs
            // 'database_password' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect('install/setup')->withErrors($validator)->withInput();
        }

        // This is covered in the "PrivilegesCheckerTest".
        // @codeCoverageIgnoreStart
        if (! app()->runningUnitTests()) {
            try {
                $privileges = new PrivilegesChecker(
                    new DatabaseTest($this->testDatabaseConnection($request))
                );

                $privileges->check();
            } catch (\Exception $e) {
                $this->setDatabaseTestsErrors($validator, $e);

                return redirect('install/setup')
                    ->withErrors($validator)
                    ->withInput();
            }
        }
        // @codeCoverageIgnoreEnd

        if (! $environmentManager->saveEnvFile(new Environment(
            name: $request->app_name,
            key: config('app.key'),
            identificationKey: config('core.key'),
            url: $request->app_url,
            dbHost: $request->database_hostname,
            dbPort: $request->database_port,
            dbName: $request->database_name,
            dbUser: $request->database_username,
            dbPassword: $request->database_password ?: '',
        ))) {
            return redirect('install/setup')->withErrors([
                'general' => static::ENV_WRITE_FAILED_MESSAGE,
            ]);
        }

        session([
            'install_country' => $request->country,
            'install_currency' => $request->currency,
        ]);

        // Use the request app_url parameter as the user may have changed
        // the url and will have different value in the .env file
        return redirect(rtrim($request->app_url, '/').'/install/database');
    }

    /**
     * Migrate the database.
     */
    public function database(): RedirectResponse
    {
        $this->migrateDatabase();

        return redirect('install/user');
    }

    /**
     * Display the user step.
     */
    public function user(): View
    {
        $step = 4;

        ViewFacade::share(['step' => $step]);

        return view('installer.user');
    }

    /**
     * Store the user.
     */
    public function userStore(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', StringRule::make()],
            'email' => ['required', StringRule::make(), 'email', 'unique:users'],
            'timezone' => 'required|timezone:all',
            'password' => 'required|string|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect('install/user')->withErrors($validator)->withInput();
        }

        User::unguarded(function () use ($request) {
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'timezone' => $request->timezone,
                'password' => Hash::make($request->password),
                'super_admin' => true,
                'access_api' => true,
                'time_format' => DefaultSettings::get('time_format'),
                'date_format' => DefaultSettings::get('date_format'),
            ]);
        });

        return redirect('install/finalize');
    }

    /**
     * Display the finish step or apply patches.
     */
    public function finished(Patcher $patcher, Request $request): View|RedirectResponse
    {
        if ($request->isMethod('POST')) {
            return $this->patch($request);
        }

        $step = 5;
        $user = User::first();

        if (app(RequirementsChecker::class)->passes('zip')) {
            try {
                $patches = $patcher->getAvailablePatches()->reject->isApplied();
            } catch (\Exception) {
                // Do nothing if any exception is thrown
            }
        }

        ViewFacade::share(['step' => $step]);

        return view('installer.finish', [
            'user' => $user,
            'patches' => $patches ?? [],
            'phpExecutable' => \Modules\Core\App\Application::getPhpExecutablePath(),
            'minPHPVersion' => config('installer.core.minPhpVersion'),
        ]);
    }

    /**
     * Finalize the installation with redirect.
     */
    public function finalize(): RedirectResponse
    {
        $errors = null;

        $currency = session()->pull('install_currency', settings('currency'));
        $countryId = session()->pull('install_country', settings('company_country_id'));

        try {
            $this->finalizeInstallation($currency, $countryId);
        } catch (FailedToFinalizeInstallationException $e) {
            $errors = $e->getMessage();
        }

        $route = URL::temporarySignedRoute(
            'install.finished',
            now()->addMinutes(60)
        );

        return redirect($route)->withErrors(['general' => $errors]);
    }

    /**
     * Apply the available patches.
     */
    protected function patch(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->input(), [
            'purchase_key' => 'required',
        ]);

        $backWithErrors = function ($validator) {
            return back()->withErrors($validator)->withInput();
        };

        if ($validator->fails()) {
            return $backWithErrors($validator);
        }

        if ($request->filled('purchase_key')) {
            settings(['purchase_key' => $request->input('purchase_key')]);
        }

        // Resolve after setting the purchase key it reflects the config
        $patcher = app(Patcher::class);

        try {
            $patcher->getAvailablePatches()->reject->isApplied()
                ->each(function ($patch) use ($patcher) {
                    $patcher->apply($patch->token());
                });
        } catch (UpdaterException $e) {
            $validator->getMessageBag()->add('general', $e->getMessage());

            return $backWithErrors($validator);
        }

        return back();
    }

    /**
     * Set the database tests errors.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @param  \Exception  $e
     */
    protected function setDatabaseTestsErrors($validator, $e): void
    {
        // https://stackoverflow.com/questions/41835923/syntax-error-or-access-violation-1115-unknown-character-set-utf8mb4
        if (strstr($e->getMessage(), 'Unknown character set')) {
            $validator->getMessageBag()->add('general', 'At least MySQL 5.6 version is required.');
        } elseif ($e instanceof PrivilegeNotGrantedException) {
            $validator->getMessageBag()->add('privilege', 'The '.$e->getPriviligeName().' privilige is not granted to the database user, the following error occured during tests: '.$e->getMessage());
        } else {
            $validator->getMessageBag()->add('general', 'Could not establish database connection: '.$e->getMessage());
            $validator->getMessageBag()->add('database_hostname', 'Please check entered value.');
            $validator->getMessageBag()->add('database_port', 'Please check entered value.');
            $validator->getMessageBag()->add('database_name', 'Please check entered value.');
            $validator->getMessageBag()->add('database_username', 'Please check entered value.');
            $validator->getMessageBag()->add('database_password', 'Please check entered value.');
        }
    }

    /**
     * Test the database connection.
     */
    protected function testDatabaseConnection(Request $request): Connection
    {
        $config = [
            'driver' => 'mysql',
            'host' => $request->database_hostname,
            'database' => $request->database_name,
            'username' => $request->database_username,
            'password' => $request->database_password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => config('database.connections.mysql.prefix'),
        ];

        $connectionKey = 'install'.md5(json_encode($config));

        Config::set('database.connections.'.$connectionKey, $config);

        /**
         * @var \Illuminate\Database\Connection
         */
        $connection = DB::connection($connectionKey);

        // Triggers PDO init, in case of errors, will fail and throw exception
        $connection->getPdo();

        return $connection;
    }
}
