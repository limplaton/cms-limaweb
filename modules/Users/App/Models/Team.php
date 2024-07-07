<?php
 

namespace Modules\Users\App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Modules\Core\App\Common\VisibilityGroup\VisibilityDependentable;
use Modules\Core\App\Models\CacheModel;
use Modules\Users\Database\Factories\TeamFactory;

class Team extends CacheModel
{
    use HasFactory,
        VisibilityDependentable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', 'description', 'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'int',
    ];

    /**
     * Perform any actions required after the model boots.
     */
    protected static function booted(): void
    {
        static::deleting(function (Team $model) {
            $model->purge();
        });
    }

    /**
     * Get the team manager
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all of the users that belong to the team
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps()->as('membership');
    }

    /**
     * Get all of the team's users including its manager
     */
    public function allUsers(): Collection
    {
        return $this->users->merge([$this->manager]);
    }

    /**
     * Scope a query to include only the teams the user belongs to.
     */
    public function scopeUserTeams(Builder $query, ?User $user = null): void
    {
        /** @var \Modules\Users\App\Models\User */
        $user = $user ?: Auth::user();

        if ($user->isSuperAdmin()) {
            return;
        }

        $query->whereHas('users', function ($query) use ($user) {
            return $query->where('user_id', $user->getKey());
        })->orWhere('teams.user_id', $user->getKey());
    }

    /**
     * Purge the team data.
     */
    public function purge(): void
    {
        $this->users()->detach();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): TeamFactory
    {
        return TeamFactory::new();
    }
}
