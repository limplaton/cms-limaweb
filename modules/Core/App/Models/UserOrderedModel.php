<?php
 

namespace Modules\Core\App\Models;

class UserOrderedModel extends CacheModel
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
    protected $fillable = ['display_order', 'user_id'];
}
