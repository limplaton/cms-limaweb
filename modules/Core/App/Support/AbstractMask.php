<?php
 

namespace Modules\Core\App\Support;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

abstract class AbstractMask implements Arrayable, JsonSerializable
{
    /**
     * Initialize the mask
     *
     * @param  array|object  $entity
     */
    public function __construct(protected $entity)
    {
    }

    /**
     * Get the entity
     *
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
