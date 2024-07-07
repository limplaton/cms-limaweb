<?php
 

namespace Modules\Core\App\Zapier;

use Exception;

class ActionNotSupportedException extends Exception
{
    /**
     * Initialize ActionNotSupportedException
     */
    public function __construct($action, $code = 0, ?Exception $previous = null)
    {
        parent::__construct("$action is not supported.", $code, $previous);
    }
}
