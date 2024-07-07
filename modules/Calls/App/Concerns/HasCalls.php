<?php
 

namespace Modules\Calls\App\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasCalls
{
    /**
     * Get all of the calls for the model.
     */
    public function calls(): MorphToMany
    {
        return $this->morphToMany(\Modules\Calls\App\Models\Call::class, 'callable');
    }
}
