<?php
 

namespace Modules\Core\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\App\Models\Import;
use Modules\Users\App\Models\User;

class ImportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user is allowed to perform import.
     */
    public function import(User $user, Import $import)
    {
        return $this->isSameUser($user, $import);
    }

    /**
     * Determine whether the user can delete the import.
     */
    public function delete(User $user, Import $import): bool
    {
        return $this->isSameUser($user, $import);
    }

    /**
     * Determine whether the user can revert the import.
     */
    public function revert(User $user, Import $import): bool
    {
        return $this->isSameUser($user, $import);
    }

    /**
     * Determine whether the user can upload fixed skip file.
     */
    public function uploadFixedSkipFile(User $user, Import $import): bool
    {
        return $this->isSameUser($user, $import);
    }

    /**
     *Determine whether the user can upload fixed skip file
     */
    public function downloadSkipFile(User $user, Import $import): bool
    {
        return $this->isSameUser($user, $import);
    }

    protected function isSameUser(User $user, Import $import): bool
    {
        return (int) $import->user_id === (int) $user->id;
    }
}
