<?php
 

namespace Modules\Core\App\Updater\Exceptions;

use Exception;

class PurchaseKeyEmptyException extends UpdaterException
{
    /**
     * Initialize new PurchaseKeyEmptyException instance
     *
     * @param  string  $message
     * @param  int  $code
     */
    public function __construct($message = '', $code = 0, ?Exception $previous = null)
    {
        parent::__construct('Purchase key not provided [empty].', 400, $previous);
    }
}
