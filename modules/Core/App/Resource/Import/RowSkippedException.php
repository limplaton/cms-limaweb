<?php
 

namespace Modules\Core\App\Resource\Import;

use Exception;

class RowSkippedException extends Exception
{
    /**
     * @var \Modules\Core\App\Resource\Import\Failure[]
     */
    protected array $failures;

    /**
     * Create new RowSkippedException instance.
     */
    public function __construct(Failure ...$failures)
    {
        $this->failures = $failures;

        parent::__construct();
    }

    /**
     * @return \Modules\Core\App\Resource\Import\Failure[]
     */
    public function failures(): array
    {
        return $this->failures;
    }
}
