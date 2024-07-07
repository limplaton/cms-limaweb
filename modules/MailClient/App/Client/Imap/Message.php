<?php
 

namespace Modules\MailClient\App\Client\Imap;

use Illuminate\Support\Carbon;
use Modules\Core\App\Common\Mail\Headers\AddressHeader;
use Modules\Core\App\Common\Mail\Headers\HeadersCollection;
use Modules\MailClient\App\Client\AbstractMessage;
use Modules\MailClient\App\Client\FolderIdentifier;
use ZBateson\MailMimeParser\MailMimeParser;

class Message extends AbstractMessage
{
    /**
     * The message headers
     *
     * @var \Modules\Core\App\Common\Mail\Headers\HeadersCollection|null
     */
    protected $headers;

    /**
     * The message folder
     *
     * @var \Modules\MailClient\App\Client\Imap\Folder
     */
    protected $folder;

    /**
     * Get the message uuid
     *
     * @return int
     */
    public function getId()
    {
        return $this->getEntity()->getNumber();
    }

    /**
     * Get the internet message id
     *
     * @return string|null
     */
    public function getMessageId()
    {
        $messageIdHeader = $this->getHeaders()->find('message-id');

        if ($messageIdHeader) {
            return $messageIdHeader->getValue();
        }
    }

    /**
     * Get the message id
     *
     * @return string|null
     */
    public function getSubject()
    {
        return $this->getEntity()->getSubject();
    }

    /**
     * Get the message date
     *
     * @return \Illuminate\Support\Carbon
     */
    public function getDate()
    {
        $date = $this->getEntity()->getDate();
        $tz = config('app.timezone');

        return Carbon::parse($date ?? null)->tz($tz);
    }

    /**
     * Get the Message text body
     *
     * @return string|null
     */
    public function getTextBody()
    {
        $body = $this->getEntity()->getBodyText();

        // Some messages are not properly encoded when using IMAP
        // Ticket 193
        if (! is_null($body) && mb_detect_encoding($body, 'ISO-8859-1') === 'ISO-8859-1') {
            $body = mb_convert_encoding($body, 'UTF-8', 'ISO-8859-1');
        }

        return $body;
    }

    /**
     * Get the message HTML body
     *
     * @return string|null
     */
    public function getHTMLBody()
    {
        return $this->getEntity()->getBodyHtml();
    }

    /**
     * Get the messsage attachments
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAttachments()
    {
        return $this->maskAttachments($this->getEntity()->getAttachments());
    }

    /**
     * Get message from
     *
     * @return \Modules\Core\App\Common\Mail\Headers\AddressHeader|null
     */
    public function getFrom()
    {
        $from = $this->getEntity()->getFrom();

        if (! $from) {
            return null;
        }

        return new AddressHeader('from', $from->getAddress(), $from->getName());
    }

    /**
     * Get message to
     *
     * @return \Modules\Core\App\Common\Mail\Headers\AddressHeader|null
     */
    public function getTo()
    {
        return $this->parseAddresses('to', $this->getEntity()->getTo());
    }

    /**
     * Get message CC
     *
     * @return \Modules\Core\App\Common\Mail\Headers\AddressHeader|null
     */
    public function getCc()
    {
        return $this->parseAddresses('cc', $this->getEntity()->getCc());
    }

    /**
     * Get message bcc
     *
     * @return \Modules\Core\App\Common\Mail\Headers\AddressHeader|null
     */
    public function getBcc()
    {
        return $this->parseAddresses('bcc', $this->getEntity()->getBcc());
    }

    /**
     * Get message reply to
     *
     * @return \Modules\Core\App\Common\Mail\Headers\AddressHeader|null
     */
    public function getReplyTo()
    {
        return $this->parseAddresses('reply-to', $this->getEntity()->getReplyTo());
    }

    /**
     * Get message sender
     *
     * @return \Modules\Core\App\Common\Mail\Headers\AddressHeader|null
     */
    public function getSender()
    {
        $sender = $this->getEntity()->getSender();

        // The ddeboer library thinks that multiple senders
        // may exists
        if (count($sender) > 0) {
            return new AddressHeader('sender', $sender[0]->getAddress(), $sender[0]->getName());
        }

        return null;
    }

    /**
     * Check if the message has been read/seen
     *
     * @return bool
     */
    public function isRead()
    {
        return $this->getEntity()->isSeen();
    }

    /**
     * Check whether the message is draft
     *
     * @return bool
     */
    public function isDraft()
    {
        return stripos($this->folder->getName(), 'draft') !== false;
    }

    /**
     * Mark the message as read
     *
     * @return bool
     */
    public function markAsRead()
    {
        $this->getEntity()->markAsSeen();
    }

    /**
     * Mark the message as unread
     *
     * @return bool
     */
    public function markAsUnread()
    {
        $this->getEntity()->clearFlag('\Seen');
    }

    /**
     * Get the message references
     *
     * @return array|null
     */
    public function getReferences()
    {
        $header = $this->getHeader('References');

        return $header ? $header->getIds() : null;
    }

    /**
     * Get message headers
     *
     * @return \Modules\Core\App\Common\Mail\Headers\HeadersCollection
     */
    public function getHeaders()
    {
        if (! is_null($this->headers)) {
            return $this->headers;
        }

        $mailParser = new MailMimeParser;

        $message = $mailParser->parse($this->getEntity()->getRawHeaders(), true);

        $this->headers = new HeadersCollection;

        foreach ($message->getAllHeaders() as $header) {
            $this->headers->pushHeader($header->getName(), $header->getRawValue());
        }

        return $this->headers;
    }

    /**
     * Get message header
     *
     * @param  string  $name
     * @return \Modules\Core\App\Common\Mail\Headers\Header|\Modules\Core\App\Common\Mail\Headers\AddressHeader|\Modules\Core\App\Common\Mail\Headers\IdHeader|\Modules\Core\App\Common\Mail\Headers\DateHeader|null
     */
    public function getHeader($name)
    {
        return $this->getHeaders()->find($name);
    }

    /**
     * Get the message folders remote identifiers
     *
     * @return array
     */
    public function getFolders()
    {
        return [new FolderIdentifier('name', $this->folder->getName())];
    }

    /**
     * Set the message folder
     *
     * @param  \Modules\MailClient\App\Client\Contracts\FolderInterface  $folder
     * @return static
     */
    public function setFolder($folder)
    {
        $this->folder = $folder;

        return $this;
    }

    /**
     * Mask attachments
     *
     * @param  array  $attachments
     * @return \Illuminate\Support\Collection
     */
    protected function maskAttachments($attachments)
    {
        if (! $attachments) {
            $attachments = [];
        }

        return collect($attachments)->map(function ($attachment) {
            return $this->maskAttachment($attachment);
        })->values();
    }

    /**
     * Mask attachment
     *
     * @param  mixed  $attachment
     * @return \Modules\MailClient\App\Client\Imap\Attachment
     */
    protected function maskAttachment($attachment)
    {
        return new Attachment($attachment);
    }

    /**
     * Parse Addresses
     *
     * @param  string  $type
     * @param  array  $addresses
     * @return \Modules\Core\App\Common\Mail\Headers\AddressHeader|null
     */
    protected function parseAddresses($type, $addresses)
    {
        if (! $addresses || is_array($addresses) && count($addresses) === 0) {
            return null;
        }

        $all = [];

        foreach ($addresses as $address) {
            $all[$address->getAddress()] = $address->getName();
        }

        return new AddressHeader($type, $all);
    }
}
