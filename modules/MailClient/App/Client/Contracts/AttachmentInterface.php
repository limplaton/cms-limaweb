<?php
 

namespace Modules\MailClient\App\Client\Contracts;

interface AttachmentInterface
{
    /**
     * Get attachment content id
     *
     * @return string|null
     */
    public function getContentId();

    /**
     * Get the attachment file name
     *
     * @return string
     */
    public function getFileName();

    /**
     * Get the attachment content
     *
     * @return string
     */
    public function getContent();

    /**
     * Get the attachment content type
     *
     * @return string
     */
    public function getContentType();

    /**
     * Get the attachment size
     *
     * @return int
     */
    public function getSize();

    /**
     * Get the attachment encoding
     *
     * @return string
     */
    public function getEncoding();

    /**
     * Check whether the attachment is inline
     *
     * @return bool
     */
    public function isInline();

    /**
     * Check whether the attachment is embedded message
     *
     * @return bool
     */
    public function isEmbeddedMessage();
}
