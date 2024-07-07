<?php
 

namespace Modules\Core\App\Concerns;

use Plank\Metable\Metable;

/** @mixin \Modules\Core\App\Models\Model */
trait HasMeta
{
    use Metable;
}
