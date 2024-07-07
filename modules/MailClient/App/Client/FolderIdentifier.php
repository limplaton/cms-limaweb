<?php
 

namespace Modules\MailClient\App\Client;

class FolderIdentifier
{
    /**
     * Initialize new FolderIdentifier class
     *
     * @param  string  $key
     * @param  mixed  $value
     */
    public function __construct(public $key, public $value)
    {
    }
}
