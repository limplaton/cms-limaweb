<?php
 

namespace Modules\Core\App\Settings\Stores;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use JsonException;
use Modules\Core\App\Settings\Utilities\Arr;
use RuntimeException;

/**
 * @codeCoverageIgnore
 * NOT USED YET
 */
class JsonStore extends AbstractStore
{
    protected string $path;

    protected string $backupPath;

    /**
     * Fire the post options to customize the store.
     */
    protected function postOptions(array $options)
    {
        $this->path = Arr::get($options, 'path');
        $this->backupPath = $this->path.'.backup';
    }

    /**
     * Read the data from the store.
     */
    protected function read(): array
    {
        if (! $this->filesystem()->exists($this->path) && ! $this->hasBackup()) {
            return [];
        }

        $path = $this->hasBackup() ? $this->backupPath : $this->path;
        $contents = $this->filesystem()->get($path, true);

        try {
            $data = json_decode($contents, true);
        } catch (JsonException) {
            throw new RuntimeException("Invalid JSON file in [{$this->path}]");
        }

        return (array) $data;
    }

    /**
     * Write the data into the store.
     */
    protected function write(array $data): void
    {
        $contents = $data ? json_encode($data, JSON_PRETTY_PRINT) : '{}';

        try {
            $this->filesystem()->put($this->path, $contents, true);

            if ($this->hasBackup()) {
                $this->filesystem()->delete($this->backupPath);
            }
        } catch (\ErrorException $e) {
            // file_put_contents(): Write of 1720 bytes failed with errno=71 Protocol error
            Log::error('Failed to save settings: '.$e->getMessage());
            $this->filesystem()->put($this->backupPath, $contents);
        }
    }

    /**
     * Checkw whether the settings are in backup because of failure to save
     */
    protected function hasBackup(): bool
    {
        return $this->filesystem()->exists($this->backupPath);
    }

    /**
     * Get the filesystem instance.
     */
    protected function filesystem(): Filesystem
    {
        return $this->app['files'];
    }
}
