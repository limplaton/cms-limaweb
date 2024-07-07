<?php
 

namespace Modules\Comments\App\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Comments\App\Models\Comment;
use Modules\Core\App\Models\Model;
use Modules\Users\App\Mention\PendingMention;

/** @mixin \Modules\Core\App\Models\Model */
trait HasComments
{
    /**
     * Boot the HasComments trait
     */
    protected static function bootHasComments(): void
    {
        static::deleting(function (Model $model) {
            if ($model->isReallyDeleting()) {
                $model->loadMissing('comments');

                $model->comments->each(function (Comment $comment) {
                    $comment->delete();
                });
            }
        });
    }

    /**
     * Get all of the model comments.
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')->orderBy('created_at');
    }

    /**
     * Add new comment for the commentable.
     */
    public function addComment(array $attributes): Comment
    {
        $mention = new PendingMention($attributes['body']);
        $attributes['body'] = $mention->getUpdatedText();

        $comment = $this->comments()->create($attributes);

        $comment->notifyMentionedUsers(
            $mention,
            $attributes['via_resource'] ?? null,
            $attributes['via_resource_id'] ?? null
        );

        return $comment->loadMissing('creator');
    }
}
