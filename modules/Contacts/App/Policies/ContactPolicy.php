<?php
 

namespace Modules\Contacts\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Contacts\App\Models\Contact;
use Modules\Users\App\Models\User;

class ContactPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any contacts.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the contact.
     */
    public function view(User $user, Contact $contact): bool
    {
        if ($user->can('view all contacts')) {
            return true;
        }

        if ((int) $contact->user_id === (int) $user->id) {
            return true;
        }

        if ($contact->user_id && $user->can('view team contacts')) {
            return $user->managesAnyTeamsOf($contact->user_id);
        }

        return false;
    }

    /**
     * Determine if the given user can create contacts.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the contact.
     */
    public function update(User $user, Contact $contact): bool
    {
        if ($user->can('edit all contacts')) {
            return true;
        }

        if ($user->can('edit own contacts') && (int) $user->id === (int) $contact->user_id) {
            return true;
        }

        if ($contact->user_id && $user->can('edit team contacts') && $user->managesAnyTeamsOf($contact->user_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the contact.
     */
    public function delete(User $user, Contact $contact): bool
    {
        if ($user->can('delete any contact')) {
            return true;
        }

        if ($user->can('delete own contacts') && (int) $user->id === (int) $contact->user_id) {
            return true;
        }

        if ($contact->user_id && $user->can('delete team contacts') && $user->managesAnyTeamsOf($contact->user_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user bulk delete contacts.
     */
    public function bulkDelete(User $user, ?Contact $contact = null)
    {
        if (! $contact) {
            return $user->can('bulk delete contacts');
        }

        if ($contact && $user->can('bulk delete contacts')) {
            return $this->delete($user, $contact);
        }

        return false;
    }

    /**
     * Determine whether the user can export contacts.
     */
    public function export(User $user): bool
    {
        return $user->can('export contacts');
    }
}
