<?php
 

namespace Modules\Core\App\Contracts;

interface Presentable
{
    public function displayName(): string;

    public function path(): string;

    public function getKeyName();

    public function getKey();
}
