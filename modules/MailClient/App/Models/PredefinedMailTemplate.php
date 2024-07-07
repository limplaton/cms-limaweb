<?php
 

namespace Modules\MailClient\App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Modules\Core\App\Models\Model;
use Modules\MailClient\Database\Factories\PredefinedMailTemplateFactory;

class PredefinedMailTemplate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'subject', 'body', 'is_shared', 'user_id'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_shared' => 'boolean',
        'user_id' => 'int',
    ];

    /**
     * Get the template owner
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Users\App\Models\User::class);
    }

    /**
     * Scope a query to only include templates visible for the user.
     */
    public function scopeVisibleToUser(Builder $query, ?int $userId = null): void
    {
        $query->where(function ($query) use ($userId) {
            $query->where('user_id', $userId ?: Auth::id())->orWhere('is_shared', true);
        });
    }

    /**
     * Scope a query to only include shared templates.
     */
    public function scopeShared(Builder $query): void
    {
        $query->where('is_shared', true);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PredefinedMailTemplateFactory
    {
        return PredefinedMailTemplateFactory::new();
    }
}
