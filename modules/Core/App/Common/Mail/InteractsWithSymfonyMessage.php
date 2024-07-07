<?php
 

namespace Modules\Core\App\Common\Mail;

use Illuminate\Support\Arr;

trait InteractsWithSymfonyMessage
{
    /**
     * Add the mail client headers to the symfony message
     *
     * @param  \Symfony\Component\Mime\Email  $message
     * @return static
     */
    protected function addHeadersToSymfonyMessage($message)
    {
        foreach ($this->headers as $header) {
            $message->getHeaders()->addTextHeader($header['name'], $header['value']);
        }

        return $this;
    }

    /**
     * Add symfony message header
     *
     * @param  \Symfony\Component\Mime\Email  $message
     * @param  string  $name
     * @param  string  $value
     * @return static
     */
    protected function addSymfonyMessageHeader($message, $name, $value)
    {
        $message->getHeaders()->addHeader($name, $value);

        return $this;
    }

    /**
     * Add symfony message In-Reply-To header
     *
     * @param  \Symfony\Component\Mime\Email  $message
     * @return static
     */
    protected function addSymfonyMessageInReplyToHeader($message, string $messageId)
    {
        $this->addSymfonyMessageHeader($message, 'In-Reply-To', "<$messageId>");

        return $this;
    }

    /**
     * Add symfony message References header
     *
     * @param  \Symfony\Component\Mime\Email  $message
     * @return static
     */
    protected function addSymfonyMessageReferencesHeader($message, array|string $references)
    {
        $value = array_map(fn ($id) => "<$id>", Arr::wrap($references));

        $this->addSymfonyMessageHeader($message, 'References', implode(',', $value));

        return $this;
    }

    /**
     * Add symfony message ID header
     *
     * @param  \Symfony\Component\Mime\Email  $message
     * @param  string  $name
     * @param  string  $value
     * @return static
     */
    protected function addSymfonyMessageIdHeader($message, $name, $value)
    {
        $message->getHeaders()->addIdHeader($name, $value);

        return $this;
    }
}
