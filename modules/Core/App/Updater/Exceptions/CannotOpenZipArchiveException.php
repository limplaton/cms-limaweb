<?php
 

namespace Modules\Core\App\Updater\Exceptions;

use Exception;

class CannotOpenZipArchiveException extends UpdaterException
{
    /**
     * Initialize new CannotOpenZipArchiveException instance
     *
     * @param  string  $filePath
     * @param  int  $code
     */
    public function __construct($filePath = '', $code = 0, ?Exception $previous = null)
    {
        parent::__construct('Cannot open zip archive. ['.$filePath.']', 500, $previous);
    }
}
