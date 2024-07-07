<?php
 

namespace Modules\MailClient\App\Client\Exceptions;

use Exception;
use Throwable;

class MessageNotFoundException extends Exception
{
    public function __construct($message = '', $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(
            "The message does not exist on remote server. {$message}",
            $code,
            $previous
        );
    }
}
