<?php
 

namespace Modules\MailClient\App\Client\Contracts;

use Modules\MailClient\App\Client\FolderIdentifier;

interface SmtpInterface
{
    /**
     * Set mail message subject
     *
     * @param  string  $subject
     * @return static
     */
    public function subject($subject);

    /**
     * Get mail message being composed subject
     *
     * @return string|null
     */
    public function getSubject();

    /**
     * Set mail message HTML body
     *
     * @param  string  $body
     * @return static
     */
    public function htmlBody($body);

    /**
     * Get the message being composed HTML body
     *
     * @return string|null
     */
    public function getHtmlBody();

    /**
     * Set mail message TEXT body
     *
     * @param  string  $body
     * @return static
     */
    public function textBody($body);

    /**
     * Get the message being composed TEXT body
     *
     * @return string|null
     */
    public function getTextBody();

    /**
     * Set the recipients
     *
     * @param  mixed  $recipients
     * @return static
     */
    public function to($recipients);

    /**
     * Get the message being composed To recipients
     *
     * @return array
     */
    public function getTo();

    /**
     * Set the cc address for the mail message.
     *
     * @param  array|string  $address
     * @param  string|null  $name
     * @return static
     */
    public function cc($address, $name = null);

    /**
     * Get the message being composed CC recipients
     *
     * @return array
     */
    public function getCc();

    /**
     * Set the bcc address for the mail message.
     *
     * @param  array|string  $address
     * @param  string|null  $name
     * @return static
     */
    public function bcc($address, $name = null);

    /**
     * Get the message being composed BCC recipients
     *
     * @return array
     */
    public function getBcc();

    /**
     * Set the replyTo address for the mail message.
     *
     * @param  array|string  $address
     * @param  string|null  $name
     * @return static
     */
    public function replyTo($address, $name = null);

    /**
     * Get the message being composed Reply-To recipients
     *
     * @return array
     */
    public function getReplyTo();

    /**
     * Attach a file to the message.
     *
     * @param  string  $file
     * @return static
     */
    public function attach($file, array $options = []);

    /**
     * Attach in-memory data as an attachment.
     *
     * @param  string  $data
     * @param  string  $name
     * @return static
     */
    public function attachData($data, $name, array $options = []);

    /**
     * Send mail message
     *
     * @return \Modules\MailClient\App\Client\Contracts\MessageInterface|null
     *
     * The method should return null if the email provider uses queue for sending the
     * emails, in this case, if the method return null, this means that the message
     * is queued for sending and we don't have an option to fetch the message immediately
     * after sending, we need to wait for application synchronization
     */
    public function send();

    /**
     * Set the from header email
     *
     * @param  string  $email
     */
    public function setFromAddress($email);

    /**
     * Get the from header email
     *
     * @return string|null
     */
    public function getFromAddress();

    /**
     * Set the from header name
     *
     * @param  string  $name
     */
    public function setFromName($name);

    /**
     * Get the from header name
     *
     * @return string|null
     */
    public function getFromName();

    /**
     * Add custom headers to the message
     *
     *
     * @return static
     */
    public function addHeader(string $name, string $value);

    /**
     * Set the message being composed headers
     *
     * @return array
     */
    public function setHeaders(array $headers): static;

    /**
     * Get the message being composed headers
     */
    public function getHeaders(): array;

    /**
     * Reply to a given mail message
     *
     * @param  string  $remoteId
     * @return \Modules\MailClient\App\Client\Contracts\MessageInterface|null
     *
     * The method should return null if the email provider uses queue for sending the
     * emails, in this case, if the method return null, this means that the message
     * is queued for sending and we don't have an option to fetch the message immediately
     * after sending, we need to wait for application synchronization
     */
    public function reply($remoteId, ?FolderIdentifier $folder = null);

    /**
     * Forward the given mail message
     *
     * @param  string  $remoteId
     * @return \Modules\MailClient\App\Client\Contracts\MessageInterface|null
     *
     * The method should return null if the email provider uses queue for sending the
     * emails, in this case, if the method return null, this means that the message
     * is queued for sending and we don't have an option to fetch the message immediately
     * after sending, we need to wait for application synchronization
     */
    public function forward($remoteId, ?FolderIdentifier $folder = null);
}
