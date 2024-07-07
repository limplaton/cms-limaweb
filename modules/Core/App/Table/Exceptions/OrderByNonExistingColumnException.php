<?php
 

namespace Modules\Core\App\Table\Exceptions;

use Exception;

class OrderByNonExistingColumnException extends Exception
{
    /**
     * Constructor.
     *
     * @param  string  $field
     * @param  int  $code
     */
    public function __construct($field, $code = 0, ?Exception $previous = null)
    {
        /**
         * E.q. if user added $this->orderBy('id', 'desc'); but the "id" is no available as column
         * This check is performed because in the client side the column customization component won't be able to find the
         * column name because is not in the list of this.availableColumns
         * If you want the column to be shown, you must add it to the main table
         */
        parent::__construct(
            "Order by field not exists as available table columns/field.
            If you want to order by \"{$field}\" you must add this field as available column to the table.",
            $code,
            $previous
        );
    }
}
