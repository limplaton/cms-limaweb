<?php
 

namespace Modules\MailClient\App\Client;

enum ConnectionType: string
{
    case Gmail = 'Gmail';
    case Outlook = 'Outlook';
    case Imap = 'Imap';
}
