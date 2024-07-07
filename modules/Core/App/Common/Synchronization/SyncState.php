<?php
 

namespace Modules\Core\App\Common\Synchronization;

enum SyncState: string
{
    case DISABLED = 'disabled';
    case STOPPED = 'stopped';
    case ENABLED = 'enabled';
}
