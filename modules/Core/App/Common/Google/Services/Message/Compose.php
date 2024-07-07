<?php
 

namespace Modules\Core\App\Common\Google\Services\Message;

use Google\Client;
use Google\Service\Gmail;
use Illuminate\Mail\Message;
use LogicException;
use Modules\Core\App\Common\Mail\InteractsWithSymfonyMessage;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\TextPart;

class Compose extends Message
{
    use InteractsWithSymfonyMessage;

    protected Gmail $service;

    /**
     * Create a new Compose instance.
     */
    public function __construct(protected Client $client)
    {
        parent::__construct(new Email);

        $this->service = new Gmail($client);
    }

    /**
     * Send the created mail
     */
    public function send(): Mail
    {
        $service = $this->getMessageService();

        $service->setRaw($this->createRawMessage());

        $message = $this->sendMessage($service);

        return new Mail($this->client, $message);
    }

    /**
     * Make a send message request
     *
     * @param  \Google\Service\Gmail\Message  $service
     * @return \Google\Service\Gmail\Message
     */
    protected function sendMessage($service)
    {
        return $this->service->users_messages->send('me', $service);
    }

    /**
     * Get the message service for the Gmail request
     *
     * @return \Google\Service\Gmail\Message
     */
    protected function getMessageService()
    {
        return new \Google\Service\Gmail\Message();
    }

    /**
     * Create the RAW message which is intended for the Gmail body
     * replacement of the Symfony message toString method
     *
     * We are creating our custom toString method because Symfony mailer
     * removes the BCC when converting the message to string, but we need to BCC
     * because we are using the toString method to send a message via Google services
     *
     * @see getPreparedHeaders
     *
     * @return string
     */
    protected function createRawMessage()
    {
        if (null === $body = $this->getBody()) {
            $body = new TextPart('');
        }

        return $this->base64Encode($this->getPreparedHeaders()->toString().$body->toString());
    }

    /**
     * Get the prepared message headers
     */
    public function getPreparedHeaders(): Headers
    {
        $headers = clone $this->getHeaders();

        if (! $headers->has('From')) {
            if (! $headers->has('Sender')) {
                throw new LogicException('An email must have a "From" or a "Sender" header.');
            }
            $headers->addMailboxListHeader('From', [$headers->get('Sender')->getAddress()]);
        }

        if (! $headers->has('MIME-Version')) {
            $headers->addTextHeader('MIME-Version', '1.0');
        }

        if (! $headers->has('Date')) {
            $headers->addDateHeader('Date', new \DateTimeImmutable());
        }

        // determine the "real" sender
        if (! $headers->has('Sender') && \count($froms = $headers->get('From')->getAddresses()) > 1) {
            $headers->addMailboxHeader('Sender', $froms[0]);
        }

        if (! $headers->has('Message-ID')) {
            $headers->addIdHeader('Message-ID', $this->generateMessageId());
        }

        // remove the Bcc field which should NOT be part of the sent message
        // $headers->remove('Bcc');

        return $headers;
    }

    /**
     * Prepare the Gmail message body.
     */
    protected function base64Encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), ['+' => '-', '/' => '_']), '=');
    }
}
