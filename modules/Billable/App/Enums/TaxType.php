<?php
 

namespace Modules\Billable\App\Enums;

use Modules\Core\App\Support\InteractsWithEnums;

enum TaxType: int
{
    use InteractsWithEnums;

    case exclusive = 1;
    case inclusive = 2;
    case no_tax = 3;
}
