<?php
 

namespace Modules\Core\App\Updater\Exceptions;

use Exception;

class InvalidPurchaseKeyException extends UpdaterException
{
    /**
     * Initialize new InvalidPurchaseKeyException instance
     *
     * @param  string  $message
     * @param  int  $code
     */
    public function __construct($message = '', $code = 0, ?Exception $previous = null)
    {
        parent::__construct('Invalid purchase key.', 400, $previous);
    }
}
