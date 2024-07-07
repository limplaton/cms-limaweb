<?php
 

namespace Modules\Core\App\MailableTemplate\Exceptions;

use Exception;

class CannotRenderMailableTemplate extends Exception
{
    /**
     * Throw exception
     *
     * @return Exception
     *
     * @throws CannotRenderMailableTemplate
     */
    public static function layoutDoesNotContainABodyPlaceHolder()
    {
        return new static('The layout does not contain a `{{{ mailBody }}}` placeholder');
    }
}
