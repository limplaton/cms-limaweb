<?php
 

namespace Modules\MailClient\App\Client\Exceptions;

use Exception;
use Throwable;

class ConnectionErrorException extends Exception
{
    public function __construct($message = '', $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(
            "A connection error occured, re-authenticate or try again later. {$message}",
            $code,
            $previous
        );
    }
}
