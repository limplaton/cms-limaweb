<?php
 

namespace Modules\Core\App\Contracts;

interface Primaryable
{
    /**
     * Check whether the model is application wide default model
     */
    public function isPrimary(): bool;
}
