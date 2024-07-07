<?php
 

namespace Modules\MailClient\App\Client;

use Modules\MailClient\App\Client\Contracts\FolderInterface;
use Modules\MailClient\App\Client\Contracts\ImapInterface;

abstract class AbstractImapClient implements ImapInterface
{
    /**
     * @var \Modules\MailClient\App\Client\FolderCollection
     */
    protected $folders;

    /**
     * Holds the custom set sent folder
     *
     * @var \Modules\MailClient\App\Client\Contracts\FolderInterface
     */
    protected $sentFolder;

    /**
     * Holds the custom set trash folder
     *
     * @var \Modules\MailClient\App\Client\Contracts\FolderInterface
     */
    protected $trashFolder;

    /**
     * Set the IMAP account sent folder
     *
     *
     * @return $this
     */
    public function setSentFolder(FolderInterface $folder)
    {
        $this->sentFolder = $folder;

        return $this;
    }

    /**
     * Set the IMAP account trash folder
     *
     *
     * @return $this
     */
    public function setTrashFolder(FolderInterface $folder)
    {
        $this->trashFolder = $folder;

        return $this;
    }

    /**
     * Get the account sent folder
     *
     * @return \Modules\MailClient\App\Client\Contracts\FolderInterface|null
     */
    public function getSentFolder()
    {
        if ($this->sentFolder) {
            return $this->sentFolder;
        }

        foreach ($this->getFolders()->flatten() as $folder) {
            if ($folder->isSent()) {
                return $folder;
            }
        }
    }

    /**
     * Get the account sent folder
     *
     * @return \Modules\MailClient\App\Client\Contracts\FolderInterface|null
     */
    public function getTrashFolder()
    {
        if ($this->trashFolder) {
            return $this->trashFolder;
        }

        foreach ($this->getFolders()->flatten() as $folder) {
            if ($folder->isTrash()) {
                return $folder;
            }
        }
    }

    /**
     * Get the account folders
     *
     * @return \Modules\MailClient\App\Client\FolderCollection
     */
    public function getFolders()
    {
        return $this->folders ??= $this->retrieveFolders();
    }

    /**
     * Helper function to get the latest sent message but actually
     * compare the message with the values passed to identify
     * if the message is equal like the one we need
     *
     * When sending a message, reply, Microsoft does not return
     * the new message, we need to fetch the message from the sent folder
     * But in many cases, when the first request is sent, Microsoft haven't
     * sent the message yet, in this case, we will make max 5 requests to check
     * if the message has been sent and return it to the front-end
     *
     * Ugly, but works
     *
     * @param  string  $subject  The original message subject
     * @param  string  $fromAddress  From Email
     * @param  array  $to  To Emails
     * @param  string  $messageId  Internet Message ID
     * @return \Modules\MailClient\App\Client\Contracts\MessageInterface|null
     */
    public function getLatestSentMessageAndStrictCompare($subject, $fromAddress, $to, $messageId)
    {
        $messageId = str_replace(['<', '>'], '', $messageId);

        // In case associative array passed
        // map the addresses into the single single
        $toAddresses = array_map(function ($value) {
            if (isset($value['address'])) {
                return $value['address'];
            }

            return $value;
        }, $to);

        $tries = 0;

        do {
            // When the tries are bigger then 3
            // this means that 3 requests are made and we haven't found
            // the latest sent message yet
            // in this case, allow some more time between the requests
            if ($tries > 3) {
                sleep(1);
            }

            if ($message = $this->getLatestSentMessage()) {
                if ($message->getSubject() === $subject &&
                $message->getFrom()->getAddress() === $fromAddress &&
                $message->getMessageId() === $messageId) {
                    // Next, we will compare the to addresses
                    $totalToMatching = 0;
                    $messageSentTo = $message->getTo()->getAll();

                    foreach ($messageSentTo as $address) {
                        if (in_array($address['address'], $toAddresses)) {
                            $totalToMatching++;
                        }
                    }

                    // Found matching message
                    if ($totalToMatching === count($toAddresses)) {
                        return $message;
                    }
                }
            }

            $tries++;
        } while ($tries < 5);
    }
}
