<?php
 

namespace Modules\Core\App\Updater\Exceptions;

use Exception;

class FailedToExtractZipException extends UpdaterException
{
    /**
     * Initialize new FailedToExtractZipException instance
     *
     * @param  string  $filePath
     * @param  int  $code
     */
    public function __construct($filePath = '', $code = 0, ?Exception $previous = null)
    {
        parent::__construct('Failed to extract zip file. ['.$this->filePath.']', 500, $previous);
    }
}
