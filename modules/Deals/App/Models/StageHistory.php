<?php
 

namespace Modules\Deals\App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class StageHistory extends Pivot
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'entered_at' => 'datetime',
        'left_at' => 'datetime',
        'deal_id' => 'int',
        'stage_id' => 'int',
    ];
}
