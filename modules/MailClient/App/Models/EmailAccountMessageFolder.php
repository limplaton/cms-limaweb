<?php
 

namespace Modules\MailClient\App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class EmailAccountMessageFolder extends Pivot
{
    /**
     * Indicates if the model has timestamps
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'message_id' => 'int',
        'folder_id' => 'int',
    ];
}
