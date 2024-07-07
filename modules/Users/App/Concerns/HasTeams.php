<?php
 

namespace Modules\Users\App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Modules\Users\App\Models\Team;
use Modules\Users\App\Models\User;
use Modules\Users\App\Support\TeamCache;

/** @mixin \Modules\Users\App\Models\User */
trait HasTeams
{
    /**
     * Get all of the teams the user belongs to.
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)->withTimestamps()->as('membership');
    }

    /**
     * Get all of the teams the user manages.
     */
    public function managedTeams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * Get all of the teams the user belongs to or manages.
     */
    public function allTeams(): Collection
    {
        return $this->teams->merge($this->managedTeams)->sortBy('name');
    }

    /**
     * Determine if the user manages the given team.
     */
    public function managesTeam(Team $team): bool
    {
        return $this->id == $team->user_id;
    }

    /**
     * Determine if the user manages any of the given user teams.
     */
    public function managesAnyTeamsOf(int $userId): bool
    {
        return TeamCache::userManagesAnyTeamsOf($this->id, $userId);
    }

    /**
     * Determine if the given users is in the same team with the user.
     */
    public function isInSameTeamWith(User $user): bool
    {
        return $this->allTeams()->contains(fn ($t) => $user->belongsToTeam($t));
    }

    /**
     * Determine if the user belongs to the given team.
     */
    public function belongsToTeam(Team $team): bool
    {
        return $this->managesTeam($team) || $this->teams->contains(
            fn ($t) => $t->id === $team->id
        );
    }

    /**
     * Scope a query to exclude users that are not part of the given user teams.
     */
    public function scopeIsInSameTeamWith(Builder $query, User $user, bool $includeCurrentUser = true): void
    {
        $query->where(function (Builder $query) use ($user, $includeCurrentUser) {
            $query
                // Include all the users the user manages
                ->ofManager($user, $includeCurrentUser)
                // Include all users from the user teams
                ->when($user->teams->isNotEmpty(), function (Builder $query) use ($user) {
                    $query->orWhereHas('teams', function (Builder $query) use ($user) {
                        $query->whereIn('team_id', $user->teams->pluck('id'));
                    });
                })
                // Include the user team managers
                ->orWhereHas('managedTeams', function (Builder $query) use ($user) {
                    $query->whereHas('users', fn (Builder $query) => $query->where('user_id', $user->id));
                });
        });
    }

    /**
     * Scope a query to include only users that are managed by the given user.
     */
    public function scopeOfManager(Builder $query, User $user, bool $includeCurrentUser = true): void
    {
        $query->where(function (Builder $query) use ($user, $includeCurrentUser) {
            $query->whereHas('teams', fn (Builder $query) => $query->where('teams.user_id', $user->id))
                ->when($includeCurrentUser, fn (Builder $query) => $query->orWhere('users.id', $user->id));
        });
    }
}
