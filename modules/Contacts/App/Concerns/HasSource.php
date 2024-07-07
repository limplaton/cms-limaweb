<?php
 

namespace Modules\Contacts\App\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @mixin \Modules\Core\App\Models\Model */
trait HasSource
{
    /**
     * An record has source
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(\Modules\Contacts\App\Models\Source::class);
    }
}
