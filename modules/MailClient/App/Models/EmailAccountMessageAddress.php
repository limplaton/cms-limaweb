<?php
 

namespace Modules\MailClient\App\Models;

use Modules\Core\App\Models\Model;

class EmailAccountMessageAddress extends Model
{
    /**
     * Indicates if the model has timestamps
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['address', 'name', 'address_type'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'message_id' => 'int',
    ];
}
