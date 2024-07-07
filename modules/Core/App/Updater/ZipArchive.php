<?php
 

namespace Modules\Core\App\Updater;

use Closure;
use Illuminate\Filesystem\Filesystem;
use Modules\Core\App\Updater\Exceptions\CannotOpenZipArchiveException;
use Modules\Core\App\Updater\Exceptions\FailedToExtractZipException;
use Modules\Core\App\Updater\Exceptions\UpdaterException;
use ZipArchive as BaseZipArchive;

class ZipArchive
{
    protected Filesystem $filesystem;

    protected array $excludedDirectories = [];

    protected array $excludedFiles = [];

    protected ?Closure $callback = null;

    /**
     * Create new ZipArchive instance.
     */
    public function __construct(protected string $path)
    {
        $this->filesystem = new Filesystem;
    }

    /**
     * Set the excluded directories.
     */
    public function excludedDirectories(string|array $dirs): static
    {
        $this->excludedDirectories = is_array($dirs) ? $dirs : func_get_args();

        return $this;
    }

    /**
     * Set the excluded files.
     */
    public function excludedFiles(string|array $files): static
    {
        $this->excludedFiles = is_array($files) ? $files : func_get_args();

        return $this;
    }

    /**
     * Extract the zip archive to the given path.
     */
    public function extract(string $to, bool $deleteSource = true): bool
    {
        $extension = pathinfo($this->path, PATHINFO_EXTENSION);

        if (preg_match('/[zZ]ip/', $extension)) {
            $extracted = $this->perform($to);

            if ($extracted && $deleteSource) {
                $this->deleteSource();
            }

            if (! $extracted) {
                throw new FailedToExtractZipException($this->path);
            }

            if ($this->callback) {
                call_user_func($this->callback);
            }

            return true;
        }

        throw new UpdaterException('File is not a zip archive. File is '.$extension.'.');
    }

    /**
     * Add a callback to be executed after the ZIP archive files are extracted.
     */
    public function after(Closure $closure): static
    {
        $this->callback = $closure;

        return $this;
    }

    /**
     * Perform the zip extraction.
     */
    protected function perform(string $to): bool
    {
        $this->removeExcludedFilesAndDirectories();

        $zip = new BaseZipArchive;

        if (true !== ($zip->open($this->path))) {
            throw new CannotOpenZipArchiveException($this->path);
        }

        $extracted = $zip->extractTo($to);

        $zip->close();

        return $extracted;
    }

    /**
     * Delete the .zip source file.
     */
    public function deleteSource(): bool
    {
        return $this->filesystem->delete($this->path);
    }

    /**
     * Remove the excluded files and directories.
     */
    protected function removeExcludedFilesAndDirectories(): void
    {
        foreach ($this->excludedDirectories as $excludedDir) {
            $this->deleteDirectory(str_replace('/', DIRECTORY_SEPARATOR, $excludedDir));
        }

        foreach ($this->excludedFiles as $excludedFile) {
            $this->deleteFile(str_replace('/', DIRECTORY_SEPARATOR, $excludedFile));
        }
    }

    /**
     * Delete file from the zip archive.
     */
    protected function deleteFile(string $name): void
    {
        $this->deleteFromZip($name, false);
    }

    /**
     * Delete directory from the zip archive.
     */
    protected function deleteDirectory(string $name): void
    {
        $this->deleteFromZip($name, true);
    }

    /**
     * Removes an entry from the zip file.
     *
     * The name is the relative path of the entry to remove (relative to the zip's root).
     */
    protected function deleteFromZip(string $name, bool $isDir): void
    {
        $zip = new BaseZipArchive;

        if (true !== ($zip->open($this->path))) {
            throw new CannotOpenZipArchiveException($this->path);
        }

        $name = rtrim($name, DIRECTORY_SEPARATOR);

        if ($isDir === true) {
            $name .= DIRECTORY_SEPARATOR;
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $info = $zip->statIndex($i);

            if (str_starts_with($info['name'], $name) === true) {
                $zip->deleteIndex($i);
            }
        }

        $zip->close();
    }

    /**
     * Check if the archive file exist.
     */
    public function exists(): bool
    {
        // Check if source archive is there but not extracted
        // We will check also the size of the zip, it can be 0 when
        // an exception is thrown via the request and the sink file will remain undeleted
        // with size 0, in this case, we need cast this version source as non-existed to allow future requests
        // for re-retrieval the release from the remote archive
        if ($this->filesystem->exists($this->path)) {
            return $this->filesystem->size($this->path) > 0;
        }

        return false;
    }
}
