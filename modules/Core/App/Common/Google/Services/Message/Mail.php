<?php
 

namespace Modules\Core\App\Common\Google\Services\Message;

use Exception;
use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Google\Service\Gmail\MessagePart;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Core\App\Common\Google\Concerns\HasDecodeableBody;
use Modules\Core\App\Common\Google\Concerns\HasHeaders;
use Modules\Core\App\Common\Google\Concerns\HasParts;
use Modules\Core\App\Common\Mail\Headers\HeadersCollection;

class Mail
{
    use HasDecodeableBody,
        HasHeaders,
        HasParts,
        ModifiesMail,
        ProvidesMailAttachments;

    /**
     * The message request payload.
     */
    protected ?MessagePart $payload = null;

    /**
     * Hold the messages parts.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $parts;

    /**
     * The Google Gmail Service.
     */
    protected Gmail $service;

    /**
     * Initialize new Mail instance.
     */
    public function __construct(protected Client $client, protected Message $message)
    {
        $this->service = new Gmail($client);

        // If payload is empty, the message is not preloaded
        // Use $message->load() to load the message data
        if ($payload = $message->getPayload()) {
            $this->payload = $payload;

            $this->headers = new HeadersCollection;

            foreach ($payload->getHeaders() as $header) {
                $this->headers->pushHeader($header->getName(), $header->getValue());
            }

            $this->parts = new Collection($payload->getParts());
        }
    }

    /**
     * Load message.
     */
    public function load(): self
    {
        $message = $this->service->users_messages->get('me', $this->getId());

        return new self($this->client, $message);
    }

    /**
     * Get Gmail ID of the message.
     *
     * Available when the message is not loaded.
     *
     * @return string
     */
    public function getId()
    {
        return $this->message->getId();
    }

    /**
     * Get the message Internet ID
     *
     * @return string
     */
    public function getInternetMessageId()
    {
        return $this->getHeaderValue('Message-ID');
    }

    /**
     * Get the message references
     *
     * @return array|null
     */
    public function getReferences()
    {
        /** @var \Modules\Core\App\Common\Mail\Headers\IdHeader */
        $header = $this->getHeaders()->find('References');

        return $header ? $header->getIds() : null;
    }

    /**
     * https://developers.google.com/gmail/api/guides/sync
     *
     * Get the message history id
     *
     * @return string
     */
    public function getHistoryId()
    {
        return $this->message->getHistoryId();
    }

    /**
     * Return a UNIX version of the date
     *
     * @return int UNIX date
     */
    public function getInternalDate()
    {
        return $this->message->getInternalDate();
    }

    /**
     * Returns the labels of the email
     * Example: [INBOX, STARRED, UNREAD]
     *
     * @return array|null
     */
    public function getLabels()
    {
        return $this->message->getLabelIds();
    }

    /**
     * Returns approximate size of the email
     *
     * @return mixed
     */
    public function getSize()
    {
        return $this->message->getSizeEstimate();
    }

    /**
     * Returns thread ID of the email
     *
     * Available when the message is not loaded
     *
     * @return string
     */
    public function getThreadId()
    {
        return $this->message->getThreadId();
    }

    /**
     * Returns the subject of the email
     *
     * @return string|null
     */
    public function getSubject()
    {
        return $this->getHeaderValue('subject');
    }

    /**
     * Returns array of name and email of each recipient
     *
     * @return \Modules\Core\App\Common\Mail\Headers\AddressHeader
     */
    public function getFrom()
    {
        return $this->getHeader('from');
    }

    /**
     * Returns the subject of the email
     *
     * @return \Modules\Core\App\Common\Mail\Headers\AddressHeader
     */
    public function getReplyTo()
    {
        return $this->getHeader('reply-to') ?? $this->getFrom();
    }

    /**
     * Returns array of name and email of each recipient
     *
     * @return \Modules\Core\App\Common\Mail\Headers\AddressHeader|null
     */
    public function getCC()
    {
        return $this->getHeader('cc');
    }

    /**
     * Returns array of name and email of each recipient
     *
     * @return \Modules\Core\App\Common\Mail\Headers\AddressHeader|null
     */
    public function getBcc()
    {
        return $this->getHeader('bcc');
    }

    /**
     * Returns array list of recipients
     *
     * @return \Modules\Core\App\Common\Mail\Headers\AddressHeader|null
     */
    public function getTo()
    {
        return $this->getHeader('to');
    }

    /**
     * Returns the original date that the email was sent
     *
     * @return \Illuminate\Support\Carbon
     */
    public function getDate()
    {
        return Carbon::parse($this->getHeaderValue('date'));
    }

    /**
     * Get the raw HTML body
     *
     * @return string
     */
    public function getRawHtmlBody()
    {
        return $this->getHtmlBody(true);
    }

    /**
     * Gets the HTML body
     *
     * @return string|null
     */
    public function getHtmlBody(bool $raw = false)
    {
        $content = $this->getBody('text/html');

        if ($raw) {
            return $content;
        }

        if (is_string($content)) {
            $body = trim(base64_decode($this->getDecodedBody($content)));

            return empty($body) ? null : $body;
        }

        return null;
    }

    /**
     * Get the base64 version of the body
     *
     * @return string|null
     */
    public function getRawPlainTextBody()
    {
        return $this->getPlainTextBody(true);
    }

    /**
     * Get the plain text body
     *
     * @return string|null
     */
    public function getPlainTextBody(bool $raw = false)
    {
        $content = $this->getBody();

        if ($raw) {
            return $content;
        }

        if (is_string($content)) {
            $body = trim(base64_decode($this->getDecodedBody($content)));

            return empty($body) ? null : $body;
        }

        return null;
    }

    /**
     * Returns a specific body part from an email
     *
     * @return null|string
     *
     * @throws \Exception
     */
    public function getBody(string $type = 'text/plain')
    {
        $parts = $this->getAllParts($this->parts);

        if ($this->payload->mimeType == $type && $parts->isEmpty()) {
            return $this->payload->body->data;
        }

        foreach ($parts as $part) {
            if ($part->mimeType == $type) {
                return $part->body->data;
            }
        }

        return null;
    }

    /**
     * Checks if message has at least one part without iterating through all parts
     */
    public function hasParts(): bool
    {
        return (bool) $this->iterateParts($this->parts, true);
    }

    /**
     * Initialize new MailReply instance.
     */
    public function reply(): MailReply
    {
        if (! $this->payload) {
            throw new Exception('Message not loaded.');
        }

        return new MailReply($this->client, $this);
    }
}
