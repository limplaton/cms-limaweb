<?php
 

namespace Modules\Core\App\Updater\Exceptions;

use Exception;

class MinPHPVersionRequirementException extends UpdaterException
{
    /**
     * Initialize new LicenseNotActiveException instance
     *
     * @param  string  $message
     * @param  int  $code
     */
    public function __construct($message = '', $code = 0, ?Exception $previous = null)
    {
        parent::__construct(
            'Your PHP version does not meet the required PHP version for the release you are trying to update.',
            400,
            $previous
        );
    }
}
