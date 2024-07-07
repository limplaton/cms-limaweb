<?php
 

namespace Modules\MailClient\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\MailClient\App\Models\PredefinedMailTemplate;
use Modules\Users\App\Models\User;

class PredefinedMailTemplatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the note.
     */
    public function view(User $user, PredefinedMailTemplate $template): bool
    {
        return (int) $user->id === (int) $template->user_id;
    }

    /**
     * Determine whether the user can update the note.
     */
    public function update(User $user, PredefinedMailTemplate $template): bool
    {
        return (int) $user->id === (int) $template->user_id;
    }

    /**
     * Determine whether the user can delete the note.
     */
    public function delete(User $user, PredefinedMailTemplate $template): bool
    {
        return (int) $user->id === (int) $template->user_id;
    }
}
