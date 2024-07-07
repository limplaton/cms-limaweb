<?php
 

namespace Modules\Core\App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;

abstract class CacheModel extends Model
{
    use Cachable;
}
