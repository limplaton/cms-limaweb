<?php
 

namespace Modules\MailClient\App\Client\Exceptions;

use Exception;
use Throwable;

class ServiceUnavailableException extends Exception
{
    /**
     * The retry after date value returned from the response either
     * via error message or header indicating when the request can be retried.
     */
    protected ?string $retryAfter = null;

    /**
     * Initialize new ServiceUnavailableException instance.
     */
    public function __construct(string $message, ?string $retryAfter = null, ?Throwable $previous = null)
    {
        $this->retryAfter = $retryAfter;

        parent::__construct($message, 0, $previous);
    }

    /**
     * Get the retry after date.
     */
    public function retryAfter(): ?string
    {
        return $this->retryAfter;
    }
}
