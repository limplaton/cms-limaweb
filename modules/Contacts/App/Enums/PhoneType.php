<?php
 

namespace Modules\Contacts\App\Enums;

use Modules\Core\App\Support\InteractsWithEnums;

enum PhoneType: int
{
    use InteractsWithEnums;

    case mobile = 1;
    case work = 2;
    case other = 3;
}
