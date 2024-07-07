<?php
 

namespace Modules\Core\App\Updater\Exceptions;

use Exception;

class PurchaseKeyUsedException extends UpdaterException
{
    /**
     * Initialize new PurchaseKeyUsedException instance
     *
     * @param  string  $message
     * @param  int  $code
     */
    public function __construct($message = '', $code = 0, ?Exception $previous = null)
    {
        parent::__construct('Purchase key already used to download the files for the requested release.', 409, $previous);
    }
}
