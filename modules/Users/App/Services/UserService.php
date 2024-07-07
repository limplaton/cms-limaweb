<?php
 

namespace Modules\Users\App\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Users\App\Models\Team;
use Modules\Users\App\Models\User;

class UserService
{
    /**
     * Create new user.
     */
    public function create(User $user, array $attributes): User
    {
        if (isset($attributes['super_admin']) && (bool) $attributes['super_admin'] === true) {
            $attributes['access_api'] = true;
        }

        $attributes['password'] = Hash::make($attributes['password']);

        $user->fill($attributes);
        $user->save();

        $user->assignRole($attributes['roles'] ?? []);

        collect($attributes['teams'] ?? [])->each(function ($teamId) use ($user) {
            try {
                $user->teams()->attach(Team::findOrFail($teamId));
            } catch (ModelNotFoundException) {
                // In case the team is deleted before the invitation is accepted
            }
        });

        return $user;
    }

    /**
     * Update user.
     */
    public function update(User $model, array $attributes): User
    {
        if (isset($attributes['super_admin']) && (bool) $attributes['super_admin'] === true) {
            $attributes['access_api'] = true;
        }

        if (array_key_exists('password', $attributes)) {
            if (empty($attributes['password'])) {
                unset($attributes['password']);
            } else {
                $attributes['password'] = Hash::make($attributes['password']);
            }
        }

        $model->fill($attributes)->save();

        if (isset($attributes['roles'])) {
            $model->syncRoles($attributes['roles']);
        }

        return $model;
    }

    /**
     * Delete user.
     */
    public function delete(User $model, ?int $transferDataTo = null): bool
    {
        if ($model->id === Auth::id()) {
            /**
             * User cannot delete own account
             */
            abort(409, __('users::user.delete_own_account_warning'));
        } elseif ($transferDataTo === $model->id) {
            /**
             * User cannot transfer the data to the same user
             */
            abort(409, __('users::user.delete_transfer_to_same_user_warning'));
        }

        /**
         * The data must be transfered because of foreign keys
         */
        (new TransferUserDataService($transferDataTo ?? Auth::id(), $model))();

        $model->teams()->detach();

        /**
         * Purge user non-shared filters, shared filters will be transfered.
         */
        $model->filters()->where('is_shared', 0)->delete();

        $model->zapierHooks()->delete();
        $model->dashboards()->delete();

        $model->notifications()->delete();
        $model->comments->each->delete();
        $model->imports->each->delete();

        if ($model->avatar) {
            UserAvatarService::remove($model);
        }

        $model->personalEmailAccounts->each->delete();
        $model->oAuthAccounts->each->delete();

        return $model->delete();
    }
}
