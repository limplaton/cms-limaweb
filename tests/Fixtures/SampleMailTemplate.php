<?php

namespace Tests\Fixtures;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Modules\Core\App\Common\Placeholders\Placeholders;
use Modules\Core\App\MailableTemplate\DefaultMailable;
use Modules\Core\App\MailableTemplate\MailableTemplate;

class SampleMailTemplate extends MailableTemplate
{
    use Queueable, SerializesModels;

    /**
     * The mailable variables/placeholders
     */
    public function placeholders(): Placeholders
    {
        return new Placeholders([]);
    }

    /**
     * Provides the mail template default configuration
     */
    public static function default(): DefaultMailable
    {
        return new DefaultMailable(static::defaultHtmlTemplate(), static::defaultSubject(), static::defaultTextMessage());
    }

    /**
     * Provides the mail template default message
     */
    public static function defaultHtmlTemplate(): string
    {
        return 'Sample message';
    }

    /**
     * Provides the mail template default subject
     */
    public static function defaultSubject(): string
    {
        return 'Sample subject';
    }

    /**
     * Provides the mail template default text message
     */
    public static function defaultTextMessage(): string
    {
        return 'Sample text message';
    }

    /**
     * Get the mailable human readable name
     */
    public static function name(): string
    {
        return 'Sample template';
    }
}
