<?php
 

namespace Modules\Core\App\Macros\Filesystem;

class DeepCleanDirectory
{
    /**
     * Deep clean the given directory
     *
     * @param  string  $directory
     */
    public function __invoke($directory, array $except = []): bool
    {
        if (! is_dir($directory)) {
            return false;
        }

        if (substr($directory, strlen($directory) - 1, 1) != '/') {
            $directory .= '/';
        }

        $items = glob($directory.'*', GLOB_MARK);

        foreach ($items as $item) {
            if (is_dir($item)) {
                (new static())($item);
            } elseif (! in_array($item, $except)) {
                unlink($item);
            }
        }

        return @rmdir($directory);
    }
}
