<?php
 

namespace Modules\Core\App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZapierHook extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['hook', 'action', 'resource_name', 'data', 'user_id', 'zap_id'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'user_id' => 'int',
        'zap_id' => 'int',
    ];

    /**
     * Indicates if the model has timestamps
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * A hook belongs to user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Users\App\Models\User::class);
    }

    /**
     * Scope a query to only include imports of a given resource.
     */
    public function scopeByResource(Builder $query, string $resourceName): void
    {
        $query->where('resource_name', $resourceName);
    }
}
