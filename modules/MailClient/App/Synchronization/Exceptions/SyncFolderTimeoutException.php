<?php
 

namespace Modules\MailClient\App\Synchronization\Exceptions;

class SyncFolderTimeoutException extends \RuntimeException
{
    /**
     * @param  string  $account  Account email
     * @param  string  $folderName  Email account folder full name
     */
    public function __construct($account, $folderName)
    {
        parent::__construct(
            sprintf(
                'Exit because of email account "%s" folder "%s" sync exceeded max save time per batch.',
                $account,
                $folderName
            )
        );
    }
}
