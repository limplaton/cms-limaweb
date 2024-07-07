<?php
 

namespace Modules\Activities\App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\App\Models\Model;

class Guest extends Model
{
    use SoftDeletes;

    public function guestable(): MorphTo
    {
        return $this->morphTo();
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(\Modules\Activities\App\Models\Activity::class, 'activity_guest');
    }
}
