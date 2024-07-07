<?php
 

namespace Modules\Documents\App\Enums;

enum DocumentViewType: string
{
    case NAV_TOP = 'nav-top';
    case NAV_LEFT = 'nav-left';
    case NAV_LEFT_FULL_WIDTH = 'nav-left-full-width';
}
