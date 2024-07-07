<?php
 

namespace Modules\Documents\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Documents\App\Models\Document;
use Modules\Users\App\Models\User;

class DocumentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any documents.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the document.
     */
    public function view(User $user, Document $document): bool
    {
        if ($user->can('view all documents')) {
            return true;
        }

        if ((int) $document->user_id === (int) $user->id) {
            return true;
        }

        if ($user->can('view team documents')) {
            return $user->managesAnyTeamsOf($document->user_id);
        }

        return false;
    }

    /**
     * Determine if the given user can create documents.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the document.
     */
    public function update(User $user, Document $document): bool
    {
        if ($user->can('edit all documents')) {
            return true;
        }

        if ($user->can('edit own documents') && (int) $user->id === (int) $document->user_id) {
            return true;
        }

        if ($user->can('edit team documents') && $user->managesAnyTeamsOf($document->user_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the document.
     */
    public function delete(User $user, Document $document): bool
    {
        if ($user->can('delete any document')) {
            return true;
        }

        if ($user->can('delete own documents') && (int) $user->id === (int) $document->user_id) {
            return true;
        }

        if ($user->can('delete team documents') && $user->managesAnyTeamsOf($document->user_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user bulk delete documents.
     */
    public function bulkDelete(User $user, ?Document $document = null)
    {
        if (! $document) {
            return $user->can('bulk delete documents');
        }

        if ($document && $user->can('bulk delete documents')) {
            return $this->delete($user, $document);
        }

        return false;
    }
}
