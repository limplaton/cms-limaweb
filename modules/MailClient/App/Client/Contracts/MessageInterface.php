<?php
 

namespace Modules\MailClient\App\Client\Contracts;

use Closure;

interface MessageInterface
{
    /**
     * Get the remote identifier
     *
     * @return mixed
     */
    public function getId();

    /**
     * Get the message internet id
     *
     * It happens very raraly, but some messages does not have the x-message-id header and this  method may return null
     *
     * @return string|null
     */
    public function getMessageId();

    /**
     * Get the message subject
     *
     * @return string|null
     */
    public function getSubject();

    /**
     * Get the message date
     *
     * @return \Illuminate\Support\Carbon
     */
    public function getDate();

    /**
     * Get the message TEXT body
     *
     * @return string|null
     */
    public function getTextBody();

    /**
     * Get the message HTML body
     *
     * @return string|null
     */
    public function getHTMLBody();

    /**
     * Get the message body for preview
     *
     * @param  \Closure  $replacer  Provide a replace callback
     * @return string|null
     */
    public function getPreviewBody(?Closure $replacer = null);

    /**
     * Get the messsage attachments
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAttachments();

    /**
     * Get message FROM header
     *
     * @return \Modules\Core\App\Common\Mail\Headers\AddressHeader|null
     */
    public function getFrom();

    /**
     * Get message TO header
     *
     * @return \Modules\Core\App\Common\Mail\Headers\AddressHeader|null
     */
    public function getTo();

    /**
     * Get message CC header
     *
     * @return \Modules\Core\App\Common\Mail\Headers\AddressHeader|null
     */
    public function getCc();

    /**
     * Get message BCC header
     *
     * @return \Modules\Core\App\Common\Mail\Headers\AddressHeader|null
     */
    public function getBcc();

    /**
     * Get message Reply-to header
     *
     * @return \Modules\Core\App\Common\Mail\Headers\AddressHeader|null
     */
    public function getReplyTo();

    /**
     * Get message SENDER header
     *
     * @return \Modules\Core\App\Common\Mail\Headers\AddressHeader|null
     */
    public function getSender();

    /**
     * Check if the message has been read/seen
     *
     * @return bool
     */
    public function isRead();

    /**
     * Check whether the message is draft
     *
     * @return bool
     */
    public function isDraft();

    /**
     * Mark the message as read
     *
     * @return void
     */
    public function markAsRead();

    /**
     * Mark the message as unread
     *
     * @return void
     */
    public function markAsUnread();

    /**
     * Get the message references without the < > wrappers
     *
     * @return array|null
     */
    public function getReferences();

    /**
     * Get message headers
     *
     * @return \Modules\Core\App\Common\Mail\Headers\HeadersCollection
     */
    public function getHeaders();

    /**
     * Get message header
     *
     * @param  string  $name
     * @return \Modules\Core\App\Common\Mail\Headers\Header|\Modules\Core\App\Common\Mail\Headers\AddressHeader|\Modules\Core\App\Common\Mail\Headers\IdHeader|\Modules\Core\App\Common\Mail\Headers\DateHeader|null
     */
    public function getHeader($name);

    /**
     * Get the message folders remote identifiers
     *
     * @return array
     */
    public function getFolders();

    /**
     * Check whether the message is bounce
     *
     * @return bool
     */
    public function isBounce();
}
