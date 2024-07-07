<?php
 

namespace Modules\Core\App;

use Akaunting\Money\Currency;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Modules\Core\App\Database\Migrator;
use Modules\Core\App\Facades\Notifications;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Resource\Resource;
use Modules\Core\App\Updater\UpdateFinalizer;
use Symfony\Component\Process\PhpExecutableFinder;

class Application
{
    /**
     * The application version.
     *
     * @var string
     */
    const VERSION = '1.4.1';

    /**
     * System name that will be used over the system.
     *
     * E.q. for automated actions performed by the application or logs
     *
     * @var string
     */
    const SYSTEM_NAME = 'System';

    /**
     * Indicates the installed file.
     *
     * NOTE: Used in detached.php
     *
     * @var string
     */
    const INSTALLED_FILE = '.installed';

    /**
     * The API prefix for the application.
     *
     * @var string
     */
    const API_PREFIX = 'api';

    /**
     * Indicates if the application has "booted".
     */
    protected bool $booted = false;

    /**
     * The array of booting callbacks.
     */
    protected array $bootingCallbacks = [];

    /**
     * The array of booted callbacks.
     */
    protected array $bootedCallbacks = [];

    /**
     * Requires maintenance checks cache.
     */
    protected static ?bool $requiresMaintenance = null;

    /**
     * Registered resources.
     */
    public static ?Collection $resources = null;

    /**
     * Cache of resource names keyed by the model name.
     */
    public static array $resourcesByModel = [];

    /**
     * Provide data to views.
     */
    public static array $provideToScript = [];

    /**
     * All the additionally registered vite entrypoints.
     */
    public static array $vite = [];

    /**
     * All the custom registered scripts.
     */
    public static array $scripts = [];

    /**
     * All the custom registered styles.
     */
    public static array $styles = [];

    /**
     * Get the version number of the application.
     */
    public function version(): string
    {
        return static::VERSION;
    }

    /**
     * The the system name.
     */
    public function systemName(): string
    {
        return static::SYSTEM_NAME;
    }

    /**
     * Determine if the application has booted.
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Boot the application's service providers.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        // Once the application has booted we will also fire some "booted" callbacks
        // for any listeners that need to do work after this initial booting gets
        // finished. This is useful when ordering the boot-up processes we run.
        $this->fireAppCallbacks($this->bootingCallbacks);

        $this->booted = true;

        $this->fireAppCallbacks($this->bootedCallbacks);
    }

    /**
     * Register a new boot listener.
     */
    public function booting(callable $callback): void
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * Register a new "booted" listener.
     */
    public function booted(callable $callback): void
    {
        $this->bootedCallbacks[] = $callback;

        if ($this->isBooted()) {
            $this->fireAppCallbacks([$callback]);
        }
    }

    /**
     * Call the booting callbacks for the application.
     */
    protected function fireAppCallbacks(array $callbacks): void
    {
        foreach ($callbacks as $callback) {
            call_user_func($callback, $this);
        }
    }

    /**
     * Get the application favourite colors.
     */
    public static function favouriteColors(): array
    {
        return config('core.colors');
    }

    /**
     * Check if the application is installed.
     */
    public static function isAppInstalled(): bool
    {
        return file_exists(static::installedFileLocation());
    }

    /**
     * Run callback when the application is already installed.
     */
    public static function whenInstalled(callable $callback): void
    {
        if (! static::isAppInstalled()) {
            return;
        }

        call_user_func($callback);
    }

    /**
     * Mark the application as installed.
     *
     * @codeCoverageIgnore
     */
    public static function markAsInstalled(): bool
    {
        if (! file_exists(static::installedFileLocation())) {
            $bytes = file_put_contents(
                static::installedFileLocation(),
                'Installation Date: '.date('Y-m-d H:i:s').PHP_EOL.'Version: '.static::VERSION
            );

            $created = $bytes !== false;

            if ($created === true) {
                Environment::setInstallationDate();
            }

            return $created;
        }

        return false;
    }

    /**
     * Get the installed file location.
     */
    public static function installedFileLocation(): string
    {
        return storage_path(static::INSTALLED_FILE);
    }

    /**
     * Get the available registered resources names.
     *
     * @return string[]
     */
    public static function getResourcesNames(): array
    {
        return static::registeredResources()->map(
            fn (Resource $resource) => $resource->name()
        )->all();
    }

    /**
     * Get all the registered resources.
     *
     * @return \Illuminate\Support\Collection<object, \Modules\Core\App\Resource\Resource>
     */
    public static function registeredResources()
    {
        return is_null(static::$resources) ? collect([]) : static::$resources;
    }

    /**
     * Get the resource class by a given name.
     */
    public static function resourceByName(string $name): ?Resource
    {
        return static::registeredResources()->first(
            fn (Resource $resource) => $resource::name() === $name
        );
    }

    /**
     * Get the resource class by a given model.
     */
    public static function resourceByModel(string|Model $model): ?Resource
    {
        if (is_object($model)) {
            $model = $model::class;
        }

        if (isset(static::$resourcesByModel[$model])) {
            return static::$resourcesByModel[$model];
        }

        return static::$resourcesByModel[$model] = static::registeredResources()->first(
            fn (Resource $value) => $value::$model === $model
        );
    }

    /**
     * Get the globally searchable resources.
     *
     * @return \Illuminate\Support\Collection<object, \Modules\Core\App\Resource\Resource>
     */
    public static function globallySearchableResources()
    {
        return static::registeredResources()->filter(
            fn (Resource $resource) => $resource::$globallySearchable
        );
    }

    /**
     * Register the given resources.
     *
     * @param  \Modules\Core\App\Resource\Resource[]  $resources
     */
    public static function resources(array $resources): void
    {
        static::$resources = static::registeredResources()
            ->merge($resources)->unique(function (string|Resource $resource) {
                return is_string($resource) ? $resource : $resource::class;
            })->map(function (string|Resource $resource) {
                return is_string($resource) ? new $resource : $resource;
            })->sortBy(fn (Resource $resource) => $resource::name());
    }

    /**
     * Provide data to front-end.
     */
    public static function provideToScript(array $data): void
    {
        static::$provideToScript = array_merge_recursive(static::$provideToScript, $data);
    }

    /**
     * Get the data provided to script.
     */
    public static function getDataProvidedToScript(): array
    {
        return static::$provideToScript;
    }

    /**
     * Register vite entrypoint.
     */
    public static function vite(string $id, string|array $entryPoints, string|array $config): void
    {
        static::$vite[$id] = [
            'entryPoints' => (array) $entryPoints,
            'buildDirectory' => is_array($config) ? $config['buildDirectory'] : $config,
            'hotFile' => is_array($config) && array_key_exists('hotFile', $config) ? $config['hotFile'] : storage_path($id.'.hot'),
        ];
    }

    /**
     * Get all of the Vite scripts for output.
     */
    public static function viteOutput(): string
    {
        $output = '';

        foreach (static::viteEntryPoints() as $data) {
            $output .= Vite::useHotFile($data['hotFile'])
                ->useBuildDirectory($data['buildDirectory'])
                ->withEntryPoints($data['entryPoints'])->toHtml();
        }

        return $output;
    }

    /**
     * Determine if Vite HMR is running.
     */
    public static function isRunningViteHot(): bool
    {
        if (is_file(public_path('hot'))) {
            return true;
        }

        foreach (static::viteEntryPoints() as $data) {
            if (is_file($data['hotFile'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all of the additionally registered Vite entrypoints.
     */
    public static function viteEntryPoints(): array
    {
        return static::$vite;
    }

    /**
     * Get the Vue script src.
     */
    public static function vueSrc(): string
    {
        $version = config('app.vue_version');

        if (app()->isProduction() || ! static::isRunningViteHot()) {
            return "https://unpkg.com/vue@$version/dist/vue.global.prod.js";
        }

        return "https://unpkg.com/vue@$version/dist/vue.global.js";
    }

    /**
     * Register the given script file with the application.
     */
    public static function script(string $name, string $path): void
    {
        static::$scripts[$name] = $path;
    }

    /**
     * Get all of the additional registered scripts.
     */
    public static function scripts(): array
    {
        return static::$scripts;
    }

    /**
     * Register the given CSS file with the application.
     */
    public static function style(string $name, string $path): void
    {
        static::$styles[$name] = $path;
    }

    /**
     * Get all of the additional registered stylesheets.
     */
    public static function styles(): array
    {
        return static::$styles;
    }

    /**
     * Get the application currency.
     */
    public static function currency(string|Currency|null $currency = null): Currency
    {
        if ($currency instanceof Currency) {
            return $currency;
        }

        return new Currency($currency ?: config('core.currency') ?: 'USD');
    }

    /**
     * Get the application allowed extensions for upload.
     */
    public static function allowedUploadExtensions(): array
    {
        // Replace dots with empty in case the user add dot in the extension name
        return array_map(
            fn ($extension) => trim(Str::replaceFirst('.', '', $extension)),
            explode(',', settings('allowed_extensions') ?: '')
        );
    }

    /**
     * Check whether the app is ready for serving.
     */
    public static function readyForServing(): bool
    {
        return static::isAppInstalled() && ! static::requiresUpdateFinalization();
    }

    /**
     * Check whether the app is ready for serving.
     */
    public static function whenReadyForServing(callable $callback): void
    {
        if (! static::readyForServing()) {
            return;
        }

        call_user_func($callback);
    }

    /**
     * Check whether update finalization is required.
     */
    protected static function requiresUpdateFinalization(): bool
    {
        return app(UpdateFinalizer::class)->needed();
    }

    /**
     * Check whether the app requires maintenance.
     */
    public static function requiresMaintenance(): bool
    {
        if (is_null(static::$requiresMaintenance)) {
            static::$requiresMaintenance = static::requiresUpdateFinalization() || app(Migrator::class)->needed();
        }

        return static::$requiresMaintenance;
    }

    /**
     * Create storage symbolic link.
     */
    public static function createStorageLink(): void
    {
        static::runCommand('storage:link');
    }

    /**
     * Optimize the application.
     */
    public static function optimize(): void
    {
        static::runCommands(
            (array) config('core.commands.optimize', 'optimize')
        );
    }

    /**
     * Clear the application cache.
     */
    public static function clearCache(): void
    {
        static::runCommands(
            (array) config('core.commands.clear-cache', 'optimize:clear')
        );
    }

    /**
     * Restart the queue (if configured).
     */
    public static function restartQueue(): void
    {
        try {
            static::runCommand('queue:restart');
        } catch (\Exception) {
        }
    }

    /**
     * Execute an array of commands.
     */
    public static function runCommands(array $commands): void
    {
        foreach ($commands as $command) {
            static::runCommand($command);
        }
    }

    /**
     * Execute the given command.
     */
    public static function runCommand(string|array|null $command, array|string $params = []): mixed
    {
        if (! $command) {
            return false;
        }

        if (is_array($command)) {
            $name = $command[0];
            $params = $command[1] ?? [];
        }

        return Artisan::call($name ?? $command, $params);
    }

    /**
     * Mute all of the application communication channels.
     */
    public static function muteAllCommunicationChannels(): void
    {
        config(['mail.default' => 'array']);
        config(['broadcasting.default' => 'null']);
        Notifications::disable();
    }

    /**
     * Get the available locales.
     */
    public function locales(): array
    {
        return collect(File::directories(lang_path()))
            ->map(fn (string $locale) => basename($locale))
            ->reject(fn (string $locale) => $locale === 'vendor')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Check whether process can be run.
     */
    public static function canRunProcess(): bool
    {
        return function_exists('proc_get_status') &&
            function_exists('proc_terminate') &&
            function_exists('proc_open') &&
            function_exists('proc_close');
    }

    /**
     * Get the PHP executable path.
     */
    public static function getPhpExecutablePath(): ?string
    {
        $phpFinder = new PhpExecutableFinder;

        try {
            return $phpFinder->find();
        } catch (\Exception) {
            return null;
        }
    }
}
