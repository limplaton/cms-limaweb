<?php
 

namespace Modules\MailClient\App\Client;

use Illuminate\Support\Facades\Storage;
use Modules\MailClient\App\Client\Contracts\FolderInterface;
use Modules\MailClient\App\Client\Contracts\ImapInterface;
use Modules\MailClient\App\Client\Contracts\MessageInterface;
use Modules\MailClient\App\Client\Contracts\SmtpInterface;
use Modules\MailClient\App\Client\Events\MessageSending;

class Client implements ImapInterface, SmtpInterface
{
    /**
     * The attachments from a storage disk.
     */
    public array $diskAttachments = [];

    /**
     * Create new Client instance.
     *
     * @param  \Modules\MailClient\App\Client\Contracts\SmtpInterface&\Modules\MailClient\App\Client\AbstractSmtpClient  $smtp
     */
    public function __construct(protected ImapInterface $imap, protected SmtpInterface $smtp)
    {
        $this->smtp->setImapClient($imap);
    }

    /**
     * Get account folder
     *
     *
     * @param  string|int  $folder
     * @return \Modules\MailClient\App\Client\Contracts\Masks\Folder
     *
     * @throws \Modules\MailClient\App\Client\Exceptions\FolderNotFoundException
     */
    public function getFolder($folder)
    {
        return $this->imap->getFolder($folder);
    }

    /**
     * Retrieve the account available folders from remote server
     *
     * @return \Modules\MailClient\App\Client\FolderCollection
     */
    public function retrieveFolders()
    {
        return $this->imap->retrieveFolders();
    }

    /**
     * Get account folders
     *
     * @return \Modules\MailClient\App\Client\FolderCollection
     */
    public function getFolders()
    {
        return $this->imap->getFolders();
    }

    /**
     * Move a given message to a given folder
     *
     *
     * @return bool
     */
    public function moveMessage(MessageInterface $message, FolderInterface $folder)
    {
        return $this->imap->moveMessage($message, $folder);
    }

    /**
     * Batch move messages to a given folder
     *
     * @param  array  $messages
     * @return bool|array
     */
    public function batchMoveMessages($messages, FolderInterface $from, FolderInterface $to)
    {
        return $this->imap->batchMoveMessages($messages, $from, $to);
    }

    /**
     * Permanently batch delete messages
     *
     * @param  array  $messages
     * @return void
     */
    public function batchDeleteMessages($messages)
    {
        $this->imap->batchDeleteMessages($messages);
    }

    /**
     * Batch mark as read messages
     *
     * @param  array  $messages
     * @return bool
     *
     * @throws \Modules\MailClient\App\Client\Exceptions\ConnectionErrorException
     * @throws \Modules\MailClient\App\Client\Exceptions\FolderNotFoundException
     */
    public function batchMarkAsRead($messages, ?FolderIdentifier $folder = null)
    {
        return $this->imap->batchMarkAsRead($messages, $folder);
    }

    /**
     * Batch mark as unread messages
     *
     * @param  array  $messages
     * @return bool
     *
     * @throws \Modules\MailClient\App\Client\Exceptions\ConnectionErrorException
     * @throws \Modules\MailClient\App\Client\Exceptions\FolderNotFoundException
     */
    public function batchMarkAsUnread($messages, ?FolderIdentifier $folder = null)
    {
        return $this->imap->batchMarkAsUnread($messages, $folder);
    }

    /**
     * Get message by message identifier
     *
     *
     * @param  mixed  $id
     * @return \Modules\MailClient\App\Client\Contracts\MessageInterface
     *
     * @throws \Modules\MailClient\App\Client\Exceptions\MessageNotFoundException
     */
    public function getMessage($id, ?FolderIdentifier $folder = null)
    {
        return $this->imap->getMessage($id, $folder);
    }

    /**
     * Set the from header email
     *
     * @param  string  $email
     */
    public function setFromAddress($email)
    {
        $this->smtp->setFromAddress($email);

        return $this;
    }

    /**
     * Get the from header email
     *
     * @return string|null
     */
    public function getFromAddress()
    {
        return $this->smtp->getFromAddress();
    }

    /**
     * Set the from header name
     *
     * @param  string  $name
     */
    public function setFromName($name)
    {
        $this->smtp->setFromName($name);

        return $this;
    }

    /**
     * Get the from header name
     *
     * @return string|null
     */
    public function getFromName()
    {
        return $this->smtp->getFromName();
    }

    /**
     * Set mail message subject
     *
     * @param  string  $subject
     * @return static
     */
    public function subject($subject)
    {
        $this->smtp->subject($subject);

        return $this;
    }

    /**
     * Get mail message being composed subject
     *
     * @return string|null
     */
    public function getSubject()
    {
        return $this->smtp->getSubject();
    }

    /**
     * Set mail message HTML body
     *
     * @param  string  $body
     * @return static
     */
    public function htmlBody($body)
    {
        $this->smtp->htmlBody($body);

        return $this;
    }

    /**
     * Get the message being composed HTML body
     *
     * @return string|null
     */
    public function getHtmlBody()
    {
        return $this->smtp->getHtmlBody();
    }

    /**
     * Set mail message TEXT body
     *
     * @param  string  $body
     * @return static
     */
    public function textBody($body)
    {
        $this->smtp->textBody($body);

        return $this;
    }

    /**
     * Get the message being composed TEXT body
     *
     * @return string|null
     */
    public function getTextBody()
    {
        return $this->smtp->getTextBody();
    }

    /**
     * Set the recipients
     *
     * @param  mixed  $recipients
     * @return static
     */
    public function to($recipients)
    {
        $this->smtp->to($recipients);

        return $this;
    }

    /**
     * Get the message being composed To recipients
     *
     * @return array
     */
    public function getTo()
    {
        return $this->smtp->getTo();
    }

    /**
     * Set the cc address for the mail message.
     *
     * @param  array|string  $address
     * @param  string|null  $name
     * @return static
     */
    public function cc($address, $name = null)
    {
        $this->smtp->cc($address, $name);

        return $this;
    }

    /**
     * Get the message being composed CC recipients
     *
     * @return array
     */
    public function getCc()
    {
        return $this->smtp->getCc();
    }

    /**
     * Set the bcc address for the mail message.
     *
     * @param  array|string  $address
     * @param  string|null  $name
     * @return static
     */
    public function bcc($address, $name = null)
    {
        $this->smtp->bcc($address, $name);

        return $this;
    }

    /**
     * Get the message being composed BCC recipients
     *
     * @return array
     */
    public function getBcc()
    {
        return $this->smtp->getBcc();
    }

    /**
     * Set the replyTo address for the mail message.
     *
     * @param  array|string  $address
     * @param  string|null  $name
     * @return static
     */
    public function replyTo($address, $name = null)
    {
        $this->smtp->replyTo($address, $name);

        return $this;
    }

    /**
     * Get the message being composed Reply-To recipients
     *
     * @return array
     */
    public function getReplyTo()
    {
        return $this->smtp->getReplyTo();
    }

    /**
     * Attach a file to the message.
     *
     * @param  string  $file
     * @return static
     */
    public function attach($file, array $options = [])
    {
        $this->smtp->attach($file, $options);

        return $this;
    }

    /**
     * Attach in-memory data as an attachment.
     *
     * @param  string  $data
     * @param  string  $name
     * @return static
     */
    public function attachData($data, $name, array $options = [])
    {
        $this->smtp->attachData($data, $name, $options);

        return $this;
    }

    /**
     * Attach a file to the message from storage.
     *
     * @param  string  $path
     * @param  string|null  $name
     * @return static
     */
    public function attachFromStorage($path, $name = null, array $options = [])
    {
        return $this->attachFromStorageDisk(null, $path, $name, $options);
    }

    /**
     * Attach a file to the message from storage.
     *
     * @param  string  $disk
     * @param  string  $path
     * @param  string|null  $name
     * @return static
     */
    public function attachFromStorageDisk($disk, $path, $name = null, array $options = [])
    {
        $this->diskAttachments = collect($this->diskAttachments)->push([
            'disk' => $disk,
            'path' => $path,
            'name' => $name ?? basename($path),
            'options' => $options,
        ])->unique(function ($file) {
            return $file['name'].$file['disk'].$file['path'];
        })->all();

        return $this;
    }

    /**
     * Send mail message
     *
     * @return \Modules\MailClient\App\Client\Contracts\MessageInterface|null
     */
    public function send()
    {
        // Send mail message flag
        $this->buildDiskAttachments();

        MessageSending::dispatch($this);

        return $this->smtp->send();
    }

    /**
     * Reply to a given mail message
     *
     * @param  string  $remoteId
     * @return \Modules\MailClient\App\Client\Contracts\MessageInterface|null
     */
    public function reply($remoteId, ?FolderIdentifier $folder = null)
    {
        // Reply to mail message flag
        $this->buildDiskAttachments();

        MessageSending::dispatch($this);

        return $this->smtp->reply($remoteId, $folder);
    }

    /**
     * Forward the given mail message
     *
     * @param  string  $remoteId
     * @return \Modules\MailClient\App\Client\Contracts\MessageInterface|null
     */
    public function forward($remoteId, ?FolderIdentifier $folder = null)
    {
        // Forward mail message flag
        $this->buildDiskAttachments();

        MessageSending::dispatch($this);

        return $this->smtp->forward($remoteId, $folder);
    }

    /**
     * Add custom headers to the message
     *
     *
     * @return static
     */
    public function addHeader(string $name, string $value)
    {
        $this->smtp->addHeader($name, $value);

        return $this;
    }

    /**
     * Set the message being composed headers
     *
     * @return array
     */
    public function setHeaders(array $headers): static
    {
        $this->smtp->setHeaders($headers);

        return $this;
    }

    /**
     * Get the message being composed headers
     */
    public function getHeaders(): array
    {
        return $this->smtp->getHeaders();
    }

    /**
     * Set the IMAP sent folder
     *
     *
     * @return static
     */
    public function setSentFolder(FolderInterface $folder)
    {
        $this->imap->setSentFolder($folder);

        return $this;
    }

    /**
     * Get the sent folder
     *
     * @return \Modules\MailClient\App\Client\Contracts\FolderInterface
     */
    public function getSentFolder()
    {
        return $this->imap->getSentFolder();
    }

    /**
     * Set the IMAP trash folder
     *
     *
     * @return static
     */
    public function setTrashFolder(FolderInterface $folder)
    {
        $this->imap->setTrashFolder($folder);

        return $this;
    }

    /**
     * Get the trash folder
     *
     * @return \Modules\MailClient\App\Client\Contracts\FolderInterface
     */
    public function getTrashFolder()
    {
        return $this->imap->getTrashFolder();
    }

    /**
     * Get the latest message from the sent folder
     *
     * @return \Modules\MailClient\App\Client\Contracts\MessageInterface|null
     *
     * @throws \Modules\MailClient\App\Client\Exceptions\ConnectionErrorException
     */
    public function getLatestSentMessage()
    {
        return $this->imap->getLatestSentMessage();
    }

    /**
     * Get the IMAP client
     *
     * @return \Modules\MailClient\App\Client\Contracts\ImapInterface
     */
    public function getImap()
    {
        return $this->imap;
    }

    /**
     * Get the SMTP client
     *
     * @return \Modules\MailClient\App\Client\Contracts\SmtpInterface
     */
    public function getSmtp()
    {
        return $this->smtp;
    }

    /**
     * Add all of the disk attachments to the smtp client.
     *
     * @return void
     */
    protected function buildDiskAttachments()
    {
        foreach ($this->diskAttachments as $attachment) {
            $storage = Storage::disk($attachment['disk']);

            $this->attachData(
                $storage->get($attachment['path']),
                $attachment['name'] ?? basename($attachment['path']),
                array_merge(['mime' => $storage->mimeType($attachment['path'])], $attachment['options'])
            );
        }
    }
}
