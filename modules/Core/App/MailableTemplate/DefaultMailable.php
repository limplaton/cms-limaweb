<?php
 

namespace Modules\Core\App\MailableTemplate;

class DefaultMailable
{
    /**
     * Create new default mail template.
     */
    public function __construct(protected string $htmlMessage, protected string $subject, protected ?string $textMessage = null)
    {
    }

    /**
     * Get the mailable default HTML message
     */
    public function htmlMessage(): string
    {
        return $this->htmlMessage;
    }

    /**
     * Get the mailable default text message
     */
    public function textMessage(): ?string
    {
        return $this->textMessage;
    }

    /**
     * Get the mailable default subject
     */
    public function subject(): string
    {
        return $this->subject;
    }
}
