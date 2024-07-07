<?php
 

namespace Modules\MailClient\App\Client\Compose;

use Modules\Core\App\Common\Mail\EmbeddedImagesProcessor;
use Modules\Core\App\Common\Mail\InteractsWithSymfonyMessage;

trait PreparesSymfonyMessage
{
    use InteractsWithSymfonyMessage;

    /**
     * Prepare new symfony message with defaults
     *
     * @param  \Illuminate\Mail\Message  $message
     * @param  string  $fallbackFromAddress  Pass the FROM address in case there is no from address set
     * @return \Illuminate\Mail\Message
     */
    protected function prepareSymfonyMessage($message, $fallbackFromAddress)
    {
        $fromAddress = $this->getFromAddress() ?? $fallbackFromAddress;

        if ($this->subject) {
            $message->subject($this->subject);
        }

        $message->from($fromAddress, $this->getFromName());

        $this->addSymfonyMessageContent($message);

        foreach ($this->replyTo as $recipient) {
            $message->replyTo($recipient['address'], $recipient['name']);
        }

        // Add recipients, attachments and custom headers
        $this->buildSymfonyMessageRecipients($message)
            ->buildSymfonyMessageAttachments($message)
            ->addHeadersToSymfonyMessage($message->getSymfonyMessage());

        return $message;
    }

    /**
     * Add the content to a given message.
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return void
     */
    protected function addSymfonyMessageContent($message)
    {
        if ($this->htmlBody) {
            // Prepare the body and process any inline images
            $message->html((new EmbeddedImagesProcessor)(
                $this->htmlBody,
                $this->createInlineImagesProcessingFunction($message)
            ));
        }

        if ($this->textBody) {
            $message->text($this->textBody);
        }
    }

    /**
     * Set the message recipients
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return static
     */
    protected function buildSymfonyMessageRecipients($message)
    {
        foreach (['cc', 'bcc', 'to'] as $type) {
            foreach ($this->{$type} as $recipient) {
                $message->{$type}($recipient['address'], $recipient['name']);
            }
        }

        return $this;
    }

    /**
     * Set the message attachments
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return static
     */
    protected function buildSymfonyMessageAttachments($message)
    {
        foreach ($this->attachments as $attachment) {
            $message->attach($attachment['file'], $attachment['options']);
        }

        foreach ($this->rawAttachments as $attachment) {
            $message->attachData($attachment['data'], $attachment['name'], $attachment['options']);
        }

        return $this;
    }

    /**
     * Helper inline images processing function
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return \Closure
     */
    protected function createInlineImagesProcessingFunction($message)
    {
        return function ($data, $name, $contentType) use ($message) {
            return $message->embedData($data, $name, $contentType);
        };
    }
}
