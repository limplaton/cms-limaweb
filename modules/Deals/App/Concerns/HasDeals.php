<?php
 

namespace Modules\Deals\App\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

/** @mixin \Modules\Core\App\Models\Model */
trait HasDeals
{
    /**
     * Get all of the deals that are associated with the model.
     */
    public function deals(): MorphToMany
    {
        return $this->morphToMany(\Modules\Deals\App\Models\Deal::class, 'dealable');
    }

    /**
     * Get all of the open deals associated with the model.
     */
    public function openDeals(): MorphToMany
    {
        return $this->deals()->open();
    }

    /**
     * Get all of the won deals associated with the model.
     */
    public function wonDeals(): MorphToMany
    {
        return $this->deals()->won();
    }

    /**
     * Get all of the lost deals associated with the model.
     */
    public function lostDeals(): MorphToMany
    {
        return $this->deals()->lost();
    }

    /**
     * Get all of the closed deals associated with the model.
     */
    public function closedDeals(): MorphToMany
    {
        return $this->deals()->closed();
    }
}
