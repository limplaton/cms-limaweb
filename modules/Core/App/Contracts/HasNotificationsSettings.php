<?php
 

namespace Modules\Core\App\Contracts;

interface HasNotificationsSettings
{
    public function getNotificationPreference(string $key): array;
}
