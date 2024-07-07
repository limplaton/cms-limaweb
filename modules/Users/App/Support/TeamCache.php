<?php
 

namespace Modules\Users\App\Support;

use Illuminate\Support\Arr;
use Modules\Users\App\Models\User;

class TeamCache
{
    protected static ?array $usersTeams = null;

    public static function userManagesAnyTeamsOf(int $userId, int $ofUserId): bool
    {
        static::cacheUserTeams();

        return in_array($userId, static::$usersTeams[$ofUserId]);
    }

    public static function flush(): void
    {
        static::$usersTeams = null;
    }

    protected static function cacheUserTeams(): void
    {
        if (! static::$usersTeams) {
            static::$usersTeams = User::with(['teams' => function ($query) {
                $query->select(['teams.user_id']);
            }])
                ->get(['id'])
                ->mapWithKeys(function (User $user) {
                    return [$user->id => Arr::pluck($user->teams, 'user_id')];
                })->all();
        }
    }
}
