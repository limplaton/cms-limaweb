<?php
 

namespace Modules\MailClient\App\Client\Exceptions;

use Exception;
use Throwable;

class FolderNotFoundException extends Exception
{
    public function __construct($message = '', $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(
            "The folder the message belongs to does not exist on remote server. {$message}",
            $code,
            $previous
        );
    }
}
