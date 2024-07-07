<?php
 

namespace Modules\Comments\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Comments\App\Models\Comment;
use Modules\Users\App\Models\User;

class CommentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the comment.
     */
    public function view(User $user, Comment $comment): bool
    {
        return (int) $user->id === (int) $comment->created_by;
    }

    /**
     * Determine whether the user can update the comment.
     */
    public function update(User $user, Comment $comment): bool
    {
        return (int) $user->id === (int) $comment->created_by;
    }

    /**
     * Determine whether the user can delete the comment.
     */
    public function delete(User $user, Comment $comment): bool
    {
        return (int) $user->id === (int) $comment->created_by;
    }
}
