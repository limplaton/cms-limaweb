<?php
 

namespace Modules\Comments\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Core\App\Common\Media\HasMedia;
use Modules\Core\App\Concerns\HasCreator;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Models\Model;
use Modules\Users\App\Mention\PendingMention;

class Comment extends Model
{
    use HasCreator,
        HasFactory,
        HasMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'body',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_by' => 'int',
    ];

    /**
     * Get the parent commentable model
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the attributes that may contain pending media
     */
    public function textAttributesWithMedia(): string
    {
        return 'body';
    }

    /**
     * Notify the mentioned users for the given mention.
     *
     * @param  string|null  $viaResource
     * @param  int|null  $viaResourceId
     * @return void
     */
    public function notifyMentionedUsers(PendingMention $mention, $viaResource = null, $viaResourceId = null): static
    {
        $isViaResource = $viaResource && $viaResourceId;

        $intermediate = $isViaResource ?
            Innoclapps::resourceByName($viaResource)->newModel()->find($viaResourceId) :
            $this->commentable;

        $mention->setUrl($intermediate->path())->withUrlQueryParameter([
            ...[
                'comment_id' => $this->getKey(),
            ],
            ...array_filter([
                'section' => $isViaResource ? $this->commentable->resource()->name() : null,
                'resourceId' => $isViaResource ? $this->commentable->getKey() : null,
            ]),
        ])->notify();

        return $this;
    }
}
