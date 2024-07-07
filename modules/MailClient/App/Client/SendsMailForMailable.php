<?php
 

namespace Modules\MailClient\App\Client;

use Illuminate\Container\Container;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;
use Modules\MailClient\App\Client\Contracts\SupportSaveToSentFolderParameter;
use Modules\MailClient\App\Client\Exceptions\ConnectionErrorException;

trait SendsMailForMailable
{
    /**
     * Get the client instance that should be used to send the mailable.
     */
    abstract protected function getClient(): ?Client;

    /**
     * Handle connection error exception.
     */
    abstract protected function onConnectionError(ConnectionErrorException $e): void;

    /**
     * Send the message using the given mailer.
     *
     * @param  \Illuminate\Contracts\Mail\Factory|\Illuminate\Contracts\Mail\Mailer  $mailer
     * @return mixed
     */
    public function send($mailer)
    {
        /**
         * First, we will check if unit tests are running or tne environment is set to local
         * if yes, we will fallback to the Laravel default mail driver that is currently set
         */
        if (app()->runningUnitTests() || app()->isLocal()) {
            return parent::send($mailer);
        }

        $client = $this->getClient();

        // If there is no client, fallback to Laravel default mail driver
        if (is_null($client)) {
            return parent::send($mailer);
        }

        $this->sendViaClient($client);
    }

    /**
     * Send the mailable via client.
     */
    protected function sendViaClient(Client $client): void
    {
        // Call the build method in case some mailables are overriding the method
        // to actually build the template e.q. add attachments etc..
        Container::getInstance()->call([$this, 'build']);

        if (! $this->shouldSaveInSentFolder()) {
            // The mailables that are sent via mail client are not supposedto be saved in the sent folder to takes up space
            // however mail provider like Gmail does not allow to not save the mail in the sent folder
            // in this case, we will check if the client support to avoid saving the mail in the sent folder
            // otherwise we will set custom header so these mails can be excluded from syncing
            if ($client->getSmtp() instanceof SupportSaveToSentFolderParameter) {
                $client->getSmtp()->saveToSentFolder(false);
            } else {
                $client->addHeader('X--Mailable', true);
            }
        }

        try {
            $view = $this->buildView();
            // First we need to parse the view, which could either be a string or an array
            // containing both an HTML and plain text versions of the view which should
            // be used when sending an e-mail. We will extract both of them out here.
            [$view, $plain, $raw] = $this->parseView($view);

            $this->addContent($client, $view, $plain, $raw, $this->buildViewData());
            $this->buildSubject($client);

            $client->to($this->to)
                ->cc($this->cc)
                ->bcc($this->bcc)
                ->replyTo($this->replyTo);

            $this->buildAttachmentsViaEmailClient($client);

            $client->send();
        } catch (ConnectionErrorException $e) {
            $this->onConnectionError($e);
        }
    }

    /**
     * Parse the given view name or array.
     *
     * @param  string|array  $view
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function parseView($view)
    {
        if (is_string($view)) {
            return [$view, null, null];
        }

        // If the given view is an array with numeric keys, we will just assume that
        // both a "pretty" and "plain" view were provided, so we will return this
        // array as is, since it should contain both views with numerical keys.
        if (is_array($view) && isset($view[0])) {
            return [$view[0], $view[1], null];
        }

        // If this view is an array but doesn't contain numeric keys, we will assume
        // the views are being explicitly specified and will extract them via the
        // named keys instead, allowing the developers to use one or the other.
        if (is_array($view)) {
            return [
                $view['html'] ?? null,
                $view['text'] ?? null,
                $view['raw'] ?? null,
            ];
        }

        throw new InvalidArgumentException('Invalid view.');
    }

    /**
     * Add the content to a given client.
     *
     * @param  \Modules\MailClient\App\Client\Client  $client
     * @param  string  $view
     * @param  string  $plain
     * @param  string  $raw
     * @param  array  $data
     * @return void
     */
    protected function addContent($client, $view, $plain, $raw, $data)
    {
        if (isset($view)) {
            $client->htmlBody($this->renderView($view, $data) ?: ' ');
        }

        if (isset($plain)) {
            $client->textBody($this->renderView($plain, $data) ?: ' ');
        }

        if (isset($raw)) {
            $client->textBody($raw);
        }
    }

    /**
     * Render the given view.
     *
     * @param  string  $view
     * @param  array  $data
     * @return string
     */
    protected function renderView($view, $data)
    {
        return $view instanceof Htmlable
                ? $view->toHtml()
                : view($view, $data)->render();
    }

    /**
     * Check whether the sent message should be saved in the sent folder.
     */
    protected function shouldSaveInSentFolder(): bool
    {
        return false;
    }

    /**
     * Build the mailable attachemnts via email client.
     */
    protected function buildAttachmentsViaEmailClient(Client $client): static
    {
        foreach ($this->attachments as $attachment) {
            $client->attach($attachment['file'], $attachment['options']);
        }

        foreach ($this->rawAttachments as $attachment) {
            $client->attachData(
                $attachment['data'],
                $attachment['name'],
                $attachment['options']
            );
        }

        $client->diskAttachments = $this->diskAttachments;

        return $this;
    }
}
