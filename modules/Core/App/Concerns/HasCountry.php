<?php
 

namespace Modules\Core\App\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @mixin \Modules\Core\App\Models\Model */
trait HasCountry
{
    /**
     * A model belongs to country.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\App\Models\Country::class);
    }
}
