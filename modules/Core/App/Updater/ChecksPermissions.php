<?php
 

namespace Modules\Core\App\Updater;

use Symfony\Component\Finder\Finder;

trait ChecksPermissions
{
    /**
     * @var callable|null
     */
    protected static $permissionsCheckerFinderUsing;

    /**
     * @var callable|null
     */
    protected static $checkPermissionsUsing;

    /**
     * Indicates if the permissions checks passed.
     */
    protected bool $writeable = false;

    /**
     * Add custom permissions checker.
     */
    public static function checkPermissionsUsing(?callable $callable): void
    {
        static::$checkPermissionsUsing = $callable;
    }

    /**
     * Check a given directory recursively if all files are writeable.
     */
    protected function checkPermissions(string $path, array $excludedFolders): bool
    {
        // In case used in foreach loop e.q. when bulk applying patches
        // prevent checking the permissions multiple times.
        if ($this->writeable) {
            return $this->writeable;
        }

        $finder = $this->getPermissionsCheckerFinder($path, $excludedFolders);

        if (static::$checkPermissionsUsing) {
            return call_user_func_array(static::$checkPermissionsUsing, [$finder, $path, $excludedFolders]);
        }

        $passes = true;

        foreach ($finder as $file) {
            if ($file->isWritable() === false) {
                $passes = false;

                break;
            }
        }

        return $this->writeable = $passes;
    }

    /**
     * Get the finder instance for the permissions checker
     */
    protected function getPermissionsCheckerFinder(string $path, array $excludedFolders): Finder
    {
        if (static::$permissionsCheckerFinderUsing) {
            return call_user_func_array(static::$permissionsCheckerFinderUsing, [$path]);
        }

        return (new Finder)->exclude($excludedFolders)->notName('worker.log')->in($path);
    }

    /**
     * Provide custom permissions checker Finder instance
     */
    public static function providePermissionsCheckerFinderUsing(?callable $callback): void
    {
        static::$permissionsCheckerFinderUsing = $callback;
    }
}
