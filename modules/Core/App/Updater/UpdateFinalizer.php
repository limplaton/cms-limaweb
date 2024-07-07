<?php
 

namespace Modules\Core\App\Updater;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Modules\Core\App\Application;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Updater\Events\UpdateFinalized;
use Nwidart\Modules\Facades\Module;
use SplFileInfo;

class UpdateFinalizer
{
    protected static array $before = [];

    /**
     * The directory name the patchers are stored.
     */
    protected string $patchersDir = 'patchers';

    /**
     * Run the update finalizer.
     */
    public function run(): bool
    {
        if (! $this->needed()) {
            return false;
        }

        Innoclapps::clearCache();

        $this->runPatchers();

        settings([
            '_version' => $version = Application::VERSION,
            '_last_updated_date' => date('Y-m-d H:i:s'),
            '_updated_from' => $updatedFrom = $this->getCachedCurrentVersion(),
        ]);

        UpdateFinalized::dispatch($version, $updatedFrom);

        $this->optimizeIfNeeded();

        if (config('updater.restart_queue')) {
            Innoclapps::restartQueue();
        }

        return true;
    }

    /**
     * Check whether finalization of the update is needed.
     */
    public function needed(): bool
    {
        return version_compare(
            $this->getCachedCurrentVersion(),
            Application::VERSION, '<'
        );
    }

    /**
     * Get the cached current version.
     */
    public function getCachedCurrentVersion(): string
    {
        return settings('_version') ?: ($_SERVER['_VERSION'] ?? '1.0.7');
    }

    /**
     * Optimize the application.
     */
    protected function optimizeIfNeeded(): void
    {
        if (! app()->runningUnitTests() && app()->isProduction()) {
            Innoclapps::optimize();
        }
    }

    /**
     * Get all of the patcher classes.
     */
    protected function patchers(): Collection
    {
        $filesystem = new Filesystem;

        return collect($filesystem->files(base_path($this->patchersDir)))
            ->when(
                Module::allEnabled(),
                fn ($collection) => $collection->push(
                    ...$this->retrieveModulesPatchers($filesystem)
                )
            )
            ->filter(
                fn (SplFileInfo $file) => str_ends_with($file->getRealPath(), '.php') &&
                     str_starts_with($file->getFilename(), 'Update')
            )
            ->values()
            ->map(fn (SplFileInfo $file) => $filesystem->getRequire($file->getRealPath()))
            ->sortBy(
                fn (UpdatePatcher $patch) => $patch->version()
            )
            ->values();
    }

    /**
     * Get all of the applicable patchers for the current version.
     */
    protected function patchersForCurrentVersion(): Collection
    {
        return $this->patchers()
            // Get all the versions starting from current cached (excluding current cached as this one is already executed)
            // between the latest available update for the current version (including current)
            ->filter(
                fn ($patch) => ! (version_compare($patch->version(), $this->getCachedCurrentVersion(), '<=') ||
                    version_compare($patch->version(), Application::VERSION, '>'))
            );
    }

    /**
     * Get patchers classes from modules.
     */
    protected function retrieveModulesPatchers(Filesystem $filesystem): array
    {
        $files = [];

        foreach (Module::allEnabled() as $module) {
            $patchersPath = module_path($module->getLowerName(), $this->patchersDir);

            if ($filesystem->isDirectory($patchersPath)) {
                $files = [...$files, ...$filesystem->files($patchersPath)];
            }
        }

        return $files;
    }

    /**
     * Execute the updates patchers.
     */
    protected function runPatchers(): void
    {
        $this->patchersForCurrentVersion()->filter->shouldRun()->each->run();
    }
}
