<?php
 

namespace Modules\MailClient\App\Client;

use Exception;
use Modules\Core\App\Common\OAuth\AccessTokenProvider;
use Modules\MailClient\App\Client\Contracts\Connectable;
use Modules\MailClient\App\Client\Exceptions\ConnectionErrorException;
use Modules\MailClient\App\Client\Gmail\ImapClient as GmailImapClient;
use Modules\MailClient\App\Client\Gmail\SmtpClient as GmailSmtpClient;
use Modules\MailClient\App\Client\Imap\Config;
use Modules\MailClient\App\Client\Imap\Config as ImapConfig;
use Modules\MailClient\App\Client\Imap\ImapClient;
use Modules\MailClient\App\Client\Imap\SmtpClient;
use Modules\MailClient\App\Client\Imap\SmtpConfig;
use Modules\MailClient\App\Client\Outlook\ImapClient as OutlookImapClient;
use Modules\MailClient\App\Client\Outlook\SmtpClient as OutlookSmtpClient;

class ClientManager
{
    /**
     * Available encryption types
     */
    const ENCRYPTION_TYPES = [
        'ssl', 'tls', 'starttls',
    ];

    /**
     * Create mail client instance
     */
    public static function createClient(
        ConnectionType $connectionType,
        AccessTokenProvider|Config $imapConfig,
        AccessTokenProvider|SmtpConfig|null $smtpConfig = null,
    ): Client {
        $part = $connectionType === ConnectionType::Imap ? '' : $connectionType->value;

        return new Client(
            self::{'create'.$part.'ImapClient'}($imapConfig),
            // ?? $imapConfig is if is AccessTokenProvider
            self::{'create'.$part.'SmtpClient'}($smtpConfig ?? $imapConfig)
        );
    }

    /**
     * Create IMAP client instance
     */
    public static function createImapClient(ImapConfig $config): ImapClient
    {
        return new ImapClient($config);
    }

    /**
     * Create SMTP client instance
     */
    public static function createSmtpClient(SmtpConfig $config): SmtpClient
    {
        return new SmtpClient($config);
    }

    /**
     * Create Outlook IMAP client instance
     */
    public static function createOutlookImapClient(AccessTokenProvider $token): OutlookImapClient
    {
        return new OutlookImapClient($token);
    }

    /**
     * Create Outlook SMTP client instance
     */
    public static function createOutlookSmtpClient(AccessTokenProvider $token): OutlookSmtpClient
    {
        return new OutlookSmtpClient($token);
    }

    /**
     * Create Gmail IMAP client instance
     */
    public static function createGmailImapClient(AccessTokenProvider $token): GmailImapClient
    {
        return new GmailImapClient($token);
    }

    /**
     * Create Gmail SMTP client instance
     */
    public static function createGmailSmtpClient(AccessTokenProvider $token): GmailSmtpClient
    {
        return new GmailSmtpClient($token);
    }

    /**
     * Test server connection
     */
    public static function testConnection(Connectable $client): void
    {
        try {
            $client->testConnection();
        } catch (Exception $e) {
            throw new ConnectionErrorException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
