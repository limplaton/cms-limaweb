<?php
 

namespace Modules\Core\App\Resource\Import;

use Exception;

class RowsExceededException extends Exception
{
    /**
     * Create new RowsExceededException instance.
     */
    public function __construct(int $totalRows)
    {
        parent::__construct(
            'The maximum rows ('.$totalRows.') allowed in import file may have exceeded. Consider splitting the import data in multiple files.'
        );
    }
}
