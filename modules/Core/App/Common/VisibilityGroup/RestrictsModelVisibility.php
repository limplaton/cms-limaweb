<?php
 

namespace Modules\Core\App\Common\VisibilityGroup;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Models\ModelVisibilityGroup;
use Modules\Users\App\Models\User;

/** @mixin \Modules\Core\App\Models\Model */
trait RestrictsModelVisibility
{
    public static string $visibilityTypeTeams = 'teams';

    public static string $visibilityTypeUsers = 'users';

    public static string $visibilityTypeAll = 'all';

    /**
     * Boot the trait.
     */
    protected static function bootRestrictsModelVisibility(): void
    {
        static::deleting(function (Model $model) {
            if ($model->isReallyDeleting()) {
                $model->visibilityGroup?->delete();
            }
        });
    }

    /**
     * Get the mode visibility group.
     */
    public function visibilityGroup(): MorphOne
    {
        return $this->morphOne(ModelVisibilityGroup::class, 'visibilityable');
    }

    /**
     * Get the visibility group with it's dependencies
     */
    public function visibilityGroupData(): ?array
    {
        if (! $this->relationLoaded('visibilityGroup') || ! $this->visibilityGroup) {
            return null;
        }

        return [
            'type' => $this->visibilityGroup->type,
            'depends_on' => $this->getVisibilityDependentsIds(),
        ];
    }

    /**
     * Get teams visiblity query
     */
    protected function getTeamsVisibilityQuery(Builder $query, User $user): string
    {
        $raw = '';
        $query = clone $query;

        $query->whereHas('visibilityGroup.teams', function ($q) use (&$raw, $user) {
            // Belongs to team or manages team
            $q->whereIn('dependable_id', $user->teams->modelKeys())->orWhere('user_id', $user->getKey());
            $raw = $q->toRawSql();
        });

        return $raw;
    }

    /**
     * Get users visiblity query
     */
    protected function getUsersVisibilityQuery(Builder $query, User $user): string
    {
        $raw = '';
        $query = clone $query;

        $query->whereHas('visibilityGroup.users', function ($q) use (&$raw, $user) {
            $q->whereIn('dependable_id', [$user->getKey()]);

            $raw = $q->toRawSql();
        });

        return $raw;
    }

    /**
     * Check whether the model is visible to the given user
     */
    public function isVisible(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $this->visibilityGroup || $this->visibilityGroup->type === static::$visibilityTypeAll) {
            return true;
        }

        if ($this->visibilityGroup->type === static::$visibilityTypeTeams) {
            foreach ($this->visibilityGroup->teams as $team) {
                if ($user->belongsToTeam($team)) {
                    return true;
                }
            }
        }

        if ($this->visibilityGroup->type === static::$visibilityTypeUsers) {
            return in_array($user->getKey(), $this->visibilityGroup->users->modelKeys());
        }

        return false;
    }

    /**
     * Apply a scoped query to eager loader the visibility groups.
     */
    public function scopeWithVisibilityGroups(Builder $query): void
    {
        $query->with(['visibilityGroup.users', 'visibilityGroup.teams']);
    }

    /**
     * Apply the scope to the given Eloquent query
     */
    public function scopeVisible(Builder $query, ?User $user = null): void
    {
        /** @var \Modules\Users\App\Models\User */
        $user = $user ?: auth()->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        $query->whereHas('visibilityGroup', function ($q) use ($user, $query) {
            $q->whereRaw('CASE
                WHEN (type = "'.static::$visibilityTypeTeams.'") THEN exists ('.$this->getTeamsVisibilityQuery($query, $user).')
                WHEN (type = "'.static::$visibilityTypeUsers.'") THEN exists ('.$this->getUsersVisibilityQuery($query, $user).')
                ELSE 1=1
            END');
        })->orWhereDoesntHave('visibilityGroup');
    }

    /**
     * Get the dependent ID's when the record has visibility group applied
     */
    protected function getVisibilityDependentsIds(): array
    {
        return collect([])->when(
            $this->shouldIncludeVisibilityGroup(static::$visibilityTypeTeams),
            fn ($collection) => $collection->concat($this->visibilityGroup->teams->modelKeys())
        )->when(
            $this->shouldIncludeVisibilityGroup(static::$visibilityTypeUsers),
            fn ($collection) => $collection->concat($this->visibilityGroup->users->modelKeys())
        )->all();
    }

    /**
     * Check whether a visibility group should be included in the response
     */
    protected function shouldIncludeVisibilityGroup(string $name): bool
    {
        return $this->visibilityGroup->type === $name && $this->visibilityGroup->relationLoaded($name);
    }

    /**
     * Save a visilbity group data the model
     */
    public function saveVisibilityGroup(array $visibility): static
    {
        $type = $visibility['type'] ?? static::$visibilityTypeAll;

        if (is_null($this->visibilityGroup)) {
            $this->visibilityGroup()->save(new ModelVisibilityGroup(['type' => $type]));
            $this->load('visibilityGroup');
        } else {
            $this->visibilityGroup->update(['type' => $type]);
            $this->visibilityGroup->{static::$visibilityTypeTeams}()->detach();
            $this->visibilityGroup->{static::$visibilityTypeUsers}()->detach();
        }

        if ($type !== static::$visibilityTypeAll) {
            $this->visibilityGroup->{$type}()->attach($visibility['depends_on'] ?? []);
        }

        return $this;
    }
}
