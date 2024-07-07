<?php
 

namespace Modules\Core\App\Updater\Exceptions;

use Exception;

class HasWrongPermissionsException extends UpdaterException
{
    /**
     * Initialize new HasWrongPermissionsException instance
     *
     * @param  string  $message
     * @param  int  $code
     */
    public function __construct($message = '', $code = 0, ?Exception $previous = null)
    {
        parent::__construct('Incorrect files permissions.', 500, $previous);
    }
}
