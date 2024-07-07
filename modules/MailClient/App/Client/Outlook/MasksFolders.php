<?php
 

namespace Modules\MailClient\App\Client\Outlook;

use Modules\MailClient\App\Client\FolderCollection;

trait MasksFolders
{
    /**
     * Ignored folders by well known name property fromm Microsoft
     *
     * @var array
     */
    protected $ignoredByWellKnownName = [
        'clutter',
        'conflicts',
        'conversationhistory',
        'outbox', // https://www.techwalla.com/articles/what-is-the-outbox-in-microsoft-outlook
        'recoverableitemsdeletions', // after deleted from the DELETE folder
        'scheduled',
        'syncissues',
    ];

    /**
     * Mask folders
     *
     * @param  array  $folders
     * @return \Modules\MailClient\App\Client\FolderCollection
     */
    protected function maskFolders($folders)
    {
        return (new FolderCollection($folders))->map(function ($folder) {
            return $this->maskFolder($folder);
        })->reject(function ($folder) {
            // Email account draft folders are not supported
            return in_array($folder->getWellKnownName(), $this->ignoredByWellKnownName) || $folder->isDraft();
        })->values();
    }

    /**
     * Mask folder
     *
     * @param  mixed  $folder
     * @return \Modules\MailClient\App\Client\Outlook\Folder
     */
    protected function maskFolder($folder)
    {
        return new Folder($folder);
    }
}
