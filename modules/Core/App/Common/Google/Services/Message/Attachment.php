<?php
 

namespace Modules\Core\App\Common\Google\Services\Message;

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\MessagePart;
use Modules\Core\App\Common\Google\Concerns\HasDecodeableBody;
use Modules\Core\App\Common\Google\Concerns\HasHeaders;
use Modules\Core\App\Common\Mail\Headers\HeadersCollection;

class Attachment
{
    use HasDecodeableBody,
        HasHeaders;

    /**
     * Holds the Gmail service.
     */
    protected Gmail $service;

    /**
     * Create new Attachment instance.
     */
    public function __construct(protected Client $client, protected string $messageId, protected MessagePart $part)
    {
        $this->service = new Gmail($client);
        $this->headers = new HeadersCollection;

        foreach ($part->getHeaders() as $header) {
            $this->headers->pushHeader($header->getName(), $header->getValue());
        }
    }

    /**
     * Get the attachment ID
     *
     * @return string
     */
    public function getId()
    {
        return $this->part->getBody()->getAttachmentId();
    }

    /**
     * Get the attachment content ID.
     *
     * Available only for inline attachments with CID (Content-ID)
     *
     * @return string|null
     */
    public function getContentId()
    {
        $contentId = $this->getHeaderValue('content-id');

        if (! $contentId) {
            $contentId = $this->getHeaderValue('x-attachment-id');
        }

        return ! is_null($contentId) ? str_replace(['<', '>'], '', $contentId) : null;
    }

    /**
     * Get the attachment file name.
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->part->getFilename();
    }

    /**
     * Get the mime type of the attachment.
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->part->getMimeType();
    }

    /**
     * Checks whether the attachments is inline.
     */
    public function isInline(): bool
    {
        if ($this->getHeaderValue('content-id') || $this->getHeaderValue('x-attachment-id')) {
            return true;
        }

        return str_contains($this->getHeaderValue('content-disposition'), 'inline');
    }

    /**
     * Get the attachment encoding.
     *
     * @return string|null
     */
    public function getEncoding()
    {
        return $this->getHeaderValue('content-transfer-encoding');
    }

    /**
     * Get the approximate size of the attachment.
     *
     * @return mixed
     */
    public function getSize()
    {
        return $this->part->getBody()->getSize();
    }

    /**
     * Get the attachment content.
     *
     * @return string
     */
    public function getContent()
    {
        $attachment = $this->retrieve();

        return $this->getDecodedBody($attachment->getData());
    }

    /**
     * Retrieve the attachment from Gmail API
     *
     * @return \Google\Service\Gmail\MessagePartBody
     */
    protected function retrieve()
    {
        return $this->service->users_messages_attachments->get(
            'me',
            $this->messageId,
            $this->getId()
        );
    }
}
