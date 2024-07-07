<?php
 

namespace Modules\Core\App\Updater\Exceptions;

use Exception;

class ReleaseDoesNotExistsException extends UpdaterException
{
    /**
     * Initialize new ReleaseDoesNotExistsException instance
     *
     * @param  string  $message
     * @param  int  $code
     */
    public function __construct($message = '', $code = 0, ?Exception $previous = null)
    {
        parent::__construct('The requested release does not exists in the archive.', 404, $previous);
    }
}
