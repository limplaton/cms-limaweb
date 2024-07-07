<?php
 

namespace Modules\MailClient\App\Client;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Illuminate\Contracts\Support\Arrayable;
use Modules\MailClient\App\Client\Contracts\SmtpInterface;
use Pelago\Emogrifier\CssInliner;
use Traversable;

abstract class AbstractSmtpClient implements SmtpInterface
{
    use Smtpable;

    /**
     * The SMTP client may need to the IMAP client e.q. to fetch a message(s)
     *
     * @var \Modules\MailClient\App\Client\Contracts\ImapInterface|\Modules\MailClient\App\Client\AbstractImapClient
     */
    protected $imap;

    /**
     * The "subject" information for the message.
     *
     * @var string
     */
    protected $subject;

    /**
     * The message reply/send HTML body
     *
     * @var string|null
     */
    protected $htmlBody;

    /**
     * The message reply/send TEXT body
     *
     * @var string|null
     */
    protected $textBody;

    /**
     * The "recipients" for the message (to).
     *
     * @var array
     */
    protected $to = [];

    /**
     * The "cc" information for the message.
     *
     * @var array
     */
    protected $cc = [];

    /**
     * The "bcc" information for the message.
     *
     * @var array
     */
    protected $bcc = [];

    /**
     * The "reply-to" information for the message.
     *
     * @var array
     */
    protected $replyTo = [];

    /**
     * The attachments for the message.
     *
     * @var array
     */
    protected $attachments = [];

    /**
     * The raw attachments for the message.
     *
     * @var array
     */
    protected $rawAttachments = [];

    const CONTENT_TYPE_HTML = 'text/html';

    const CONTENT_TYPE_TEXT = 'text/plain';

    /**
     * The message custom header
     *
     * @var array
     */
    protected $headers = [
        [
            'name' => 'X--App',
            'value' => 'true',
        ],
    ];

    /**
     * Get the mail content type
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->htmlBody ? static::CONTENT_TYPE_HTML : static::CONTENT_TYPE_TEXT;
    }

    /**
     * Check whether the mail is HTML content type
     *
     * @return bool
     */
    public function isHtmlContentType()
    {
        return $this->getContentType() === static::CONTENT_TYPE_HTML;
    }

    /**
     * Check whether the mail is Text content type
     *
     * @return bool
     */
    public function isTextContentType()
    {
        return $this->getContentType() === static::CONTENT_TYPE_TEXT;
    }

    /**
     * Set mail message subject
     *
     * @param  string  $subject
     * @return static
     */
    public function subject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get mail message being composed subject
     *
     * @return string|null
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set mail message HTML body
     *
     * @param  string  $body
     * @return static
     */
    public function htmlBody($body)
    {
        $this->htmlBody = $body;

        return $this;
    }

    /**
     * Get the message being composed HTML body
     *
     * @return string|null
     */
    public function getHtmlBody()
    {
        return $this->htmlBody;
    }

    /**
     * Set mail message TEXT body
     *
     * @param  string  $body
     * @return static
     */
    public function textBody($body)
    {
        $this->textBody = $body;

        return $this;
    }

    /**
     * Get the message being composed TEXT body
     *
     * @return string|null
     */
    public function getTextBody()
    {
        return $this->textBody;
    }

    /**
     * Set the mail recipients
     *
     * @param  array|string  $address
     * @param  string|null  $name
     * @return static
     */
    public function to($address, $name = null)
    {
        return $this->addAddress($address, $name, 'to');
    }

    /**
     * Get the message being composed To recipients
     *
     * @return array
     */
    public function getTo()
    {
        return $this->to;
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
        return $this->addAddress($address, $name, 'cc');
    }

    /**
     * Get the message being composed CC recipients
     *
     * @return array
     */
    public function getCc()
    {
        return $this->cc;
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
        return $this->addAddress($address, $name, 'bcc');
    }

    /**
     * Get the message being composed BCC recipients
     *
     * @return array
     */
    public function getBcc()
    {
        return $this->bcc;
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
        return $this->addAddress($address, $name, 'replyTo');
    }

    /**
     * Get the message being composed Reply-To recipients
     *
     * @return array
     */
    public function getReplyTo()
    {
        return $this->replyTo;
    }

    /**
     * Attach a file to the message.
     *
     * @param  string  $file
     * @return static
     */
    public function attach($file, array $options = [])
    {
        $this->attachments[] = compact('file', 'options');

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
        $this->rawAttachments[] = compact('data', 'name', 'options');

        return $this;
    }

    /**
     * Add email message custom headers
     *
     *
     * @return static
     */
    public function addHeader(string $name, string $value)
    {
        $this->headers[] = [
            'name' => $name,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Set the message being composed headers
     *
     * @return array
     */
    public function setHeaders(array $headers): static
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Get the message being composed headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Sets the imap client related to this SMTP client
     *
     * @param  \Modules\MailClient\App\Client\Contracts\ImapInterface  $client
     * @return static
     */
    public function setImapClient($client)
    {
        $this->imap = $client;

        return $this;
    }

    /**
     * Create reply subject with system special reply prefix
     *
     * @param  string  $subject
     * @return string
     */
    public function createReplySubject($subject)
    {
        return config('mailclient.reply_prefix').trim(
            preg_replace($this->cleanupSubjectSearch(), '', $subject)
        );
    }

    /**
     * Create forward subject with system special forward prefix
     *
     * @param  string  $subject
     * @return string
     */
    public function createForwardSubject($subject)
    {
        return config('mailclient.forward_prefix').trim(
            preg_replace($this->cleanupSubjectSearch(), '', $subject)
        );
    }

    /**
     * Get the clean up subject search regex
     *
     * @link https://en.wikipedia.org/wiki/List_of_email_subject_abbreviations
     *
     * @return array
     */
    protected function cleanupSubjectSearch()
    {
        return [
            // Re
            '/RE\:/i', '/SV\:/i', '/Antw\:/i', '/VS\:/i', '/RE\:/i',
            '/REF\:/i', '/ΑΠ\:/i', '/ΣΧΕΤ\:/i', '/Vá\:/i', '/R\:/i',
            '/RIF\:/i', '/BLS\:/i', '/RES\:/i', '/Odp\:/i', '/YNT\:/i',
            '/ATB\:/i',
            // FW
            '/FW\:/i', '/FWD\:/i',
            '/Doorst\:/i', '/VL\:/i', '/TR\:/i', '/WG\:/i', '/ΠΡΘ\:/i',
            '/Továbbítás\:/i', '/I\:/i', '/FS\:/i', '/TRS\:/i', '/VB\:/i',
            '/RV\:/i', '/ENC\:/i', '/PD\:/i', '/İLT\:/i', '/YML\:/i',
        ];
    }

    /**
     * Create inline version of the given message
     *
     * @param  \Modules\MailClient\App\Client\Contracts\MessageInterface  $message  Previous message
     * @param  \Closure  $callback
     * @return string
     */
    protected function inlineMessage($message, $callback)
    {
        // Let's try to include the messages inline attachments
        // If the message is composed with text only, the html body may be empty
        // We won't need any replacements, will use just the text body
        $body = $message->getHtmlBody() ?
            // The callback should return either the new contentid of the inline attachment or return the data in base64
            // e.q. "data:image/jpeg;base64,...."  or any custom logic e.q. /media file path when storing the attachment
            $message->getPreviewBody($callback) :
            $message->getTextBody();

        // Maybe the message was empty?
        if (empty($body)) {
            return $body;
        }

        return CssInliner::fromHtml($body)
            ->inlineCss()
            ->renderBodyContent();
    }

    /**
     * Create reply body with quoted message
     *
     * @param  \Modules\MailClient\App\Client\Contracts\MessageInterface  $message  Previous message
     * @param  \Closure  $callback
     * @return string|null
     */
    public function createQuoteOfPreviousMessage($message, $callback)
    {
        $date = $message->getDate();
        $from = htmlentities('<').$message->getFrom()->getAddress().htmlentities('>');

        if ($name = $message->getFrom()->getPersonName()) {
            $from = $name.' '.$from;
        }

        $wroteText = 'On '.$date->format('D, M j, Y').', at '.$date->format('g:i A').' '.$from.' wrote:';
        $quote = $this->inlineMessage($message, $callback);

        // Maybe the message was empty?
        if (empty($quote)) {
            return $quote;
        }

        // 2 new lines allow the EmailReplyParser to properly determine the actual reply message
        return "\n\n".$wroteText."\n"."<blockquote class=\"_quote\">$quote</blockquote>";
    }

    /**
     * Add address
     *
     * @param  string|array  $address
     * @param  string  $name
     * @param  string  $property
     * @return static
     */
    protected function addAddress($address, $name, $property)
    {
        $this->{$property} = array_merge(
            $this->{$property},
            $this->parseAddresses($this->arrayOfAddresses($address) ? $address : [$address => $name])
        );

        return $this;
    }

    /**
     * Parse the multi-address array into the necessary format.
     *
     * ->to('some1@address.tld')
     *
     * ->to(['some3@address.tld' => 'The Name']);
     *
     * ->to(['some2@address.tld']);
     *
     * ->to(['some4@address.tld', 'other4@address.tld']);
     *
     * ->to([
     *       'recipient-with-name@address.ltd' => 'Recipient Name One',
     *       'no-name@address.ltd',
     *       'named-recipient@address.ltd' => 'Recipient Name Two',
     *  ]);
     *
     * ->to(['name' => 'Name', 'address' => 'example@address.ltd']);
     *
     * ->to([
     *     ['name' => 'Name', 'address' => 'example@address.ltd'],
     *     ['name' => 'Name', 'address' => 'example@address.ltd']
     * ]);
     *
     * ->to([['name' => 'Name', 'address' => 'example@address.ltd'], 'example@address.ltd']);
     *
     * ->to([['address' => 'example@address.ltd']]);
     *
     * ->to([
     *      ['name' => 'Name', 'address' => 'example@address.ltd'],
     *      'example@address.ltd',
     *      ['address' => 'example@address.ltd']
     * ]);
     *
     *
     * @return array
     */
    protected function parseAddresses(iterable|Arrayable $value)
    {
        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }

        $addresses = collect([]);

        if (count($value) === 2 && isset($value['address'])) {
            $addresses->push(['name' => $value['name'] ?? null, 'address' => $value['address']]);
        } else {
            foreach ($value as $address => $values) {
                if (is_array($values)) {
                    $addresses = $addresses->merge([[
                        'name' => $values['name'] ?? null,
                        'address' => $values['address'],
                    ]]);
                } elseif (is_numeric($address)) {
                    $addresses->push(['name' => null, 'address' => $values]);
                } elseif (is_null($values)) {
                    $addresses->push(['name' => null, 'address' => $address]);
                } else {
                    $addresses->push(['name' => $values, 'address' => $address]);
                }
            }
        }

        return $addresses->filter(function ($recipient) {
            return (new EmailValidator)->isValid($recipient['address'], new RFCValidation());
        })->map(fn ($recipient) => array_merge($recipient, [
            // Make sure that the recipient name is always null even when passed as empty string
            'name' => $recipient['name'] === '' ? null : $recipient['name'],
        ]))->values()->all();
    }

    /**
     * Determine if the given "address" is actually an array of addresses.
     *
     * @param  mixed  $address
     * @return bool
     */
    protected function arrayOfAddresses($address)
    {
        return is_array($address) ||
               $address instanceof Arrayable ||
               $address instanceof Traversable;
    }
}
