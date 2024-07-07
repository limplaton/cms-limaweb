<?php
 

namespace Modules\Core\App\Models;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\App\Concerns\HasCreator;

class Workflow extends Model
{
    use HasCreator;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title', 'description', 'trigger_type', 'action_type', 'data', 'created_by', 'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'is_active' => 'boolean',
        'total_executions' => 'int',
        'created_by' => 'int',
    ];

    /**
     * Scope a query to only include workflows of a given trigger type.
     */
    public function scopeByTrigger(Builder $query, string $triggerType): void
    {
        $query->where('trigger_type', $triggerType);
    }

    /**
     * Scope a query to only include active workflows.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', 1);
    }
}
