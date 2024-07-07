<?php
 

namespace Modules\Core\App\Support;

trait Makeable
{
    /**
     * Create new instance
     *
     * @param  array  $params
     */
    public static function make(...$params): static
    {
        return new static(...$params);
    }
}
