<?php
 

namespace Modules\MailClient\App\Enums;

enum EmailAccountType: string
{
    case PERSONAL = 'personal';
    case SHARED = 'shared';
}
