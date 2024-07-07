<?php
 

namespace Modules\Core\App\Updater;

use Illuminate\Filesystem\Filesystem;
use SplFileInfo;

class StaleBuildAssetsCleaner
{
    protected array $preUpdateAssets = [];

    /**
     * The path to the build directory.
     */
    protected string $buildDirectory = 'build';

    /**
     * The path to the build directory.
     */
    protected string $assetsDirectory = 'assets';

    /**
     * Initialize new StaleBuildAssetsCleaner instance.
     */
    public function __construct(protected Filesystem $filesystem)
    {

    }

    /**
     * Clean the stale assets files.
     */
    public function clean(): bool
    {
        $staleAssets = array_diff($this->preUpdateAssets, $this->getAssetsFilesPaths());

        return $this->filesystem->delete($staleAssets);
    }

    /**
     * Capture the current (old) build files.
     */
    public function capture(): static
    {
        $this->preUpdateAssets = $this->getAssetsFilesPaths();

        return $this;
    }

    /**
     * Get the assets files paths.
     */
    protected function getAssetsFilesPaths(): array
    {
        return collect($this->filesystem->allFiles($this->assetsPath()))
            ->map(fn (SplFileInfo $file) => $file->getRealPath())
            ->all();
    }

    /**
     * Get the assets path.
     */
    protected function assetsPath(): string
    {
        return public_path($this->buildDirectory.'/'.$this->assetsDirectory);
    }
}
