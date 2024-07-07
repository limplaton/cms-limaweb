<?php
 

namespace Modules\Core\App\Contracts;

interface Localizeable
{
    public function getLocalTimeFormat();

    public function getLocalDateFormat();

    public function getUserTimezone();
}
