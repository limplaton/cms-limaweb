<?php
 

namespace Modules\Documents\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Documents\App\Models\DocumentTemplate;
use Modules\Users\App\Models\User;

class DocumentTemplatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DocumentTemplate $template): bool
    {
        if ($template->is_shared) {
            return true;
        }

        return (int) $user->id === (int) $template->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DocumentTemplate $template): bool
    {
        return (int) $template->user_id === (int) $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DocumentTemplate $template): bool
    {
        return (int) $template->user_id === (int) $user->id;
    }
}
