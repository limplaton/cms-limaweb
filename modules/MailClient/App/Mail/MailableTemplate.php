<?php
 

namespace Modules\MailClient\App\Mail;

use Modules\Core\App\MailableTemplate\MailableTemplate as BaseMailableTemplate;

abstract class MailableTemplate extends BaseMailableTemplate
{
    use SendsMailableViaEmailAccount;

    /**
     * Provide the email account id
     */
    protected function emailAccountId(): ?int
    {
        if ($account = settings('system_email_account_id')) {
            return (int) $account;
        }

        return null;
    }

    /**
     * Get custom account from name text
     */
    protected function accountFromName(): string
    {
        return config('app.name') ?: '[APP NAME NOT SET]';
    }
}
