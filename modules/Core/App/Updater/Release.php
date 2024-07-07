<?php
 

namespace Modules\Core\App\Updater;

use Illuminate\Filesystem\Filesystem;

final class Release
{
    use DownloadsFiles;

    /**
     * @var \Modules\Core\App\Updater\ZipArchive
     */
    protected $archive;

    /**
     * Initialize new Relase instance.
     */
    public function __construct(protected string $version, protected Filesystem $filesystem)
    {
    }

    /**
     * Get the release version.
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Set the release version.
     */
    public function setVersion(string $version): static
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get the release archive.
     */
    public function archive(): ZipArchive
    {
        return $this->archive ??= new ZipArchive($this->getStoragePath());
    }
}
