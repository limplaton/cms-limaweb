<?php
 

namespace Modules\Notes\App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Modules\Comments\App\Concerns\HasComments;
use Modules\Core\App\Common\Media\HasMedia;
use Modules\Core\App\Common\Timeline\Timelineable;
use Modules\Core\App\Concerns\LazyTouchesViaPivot;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Resource\Resourceable;
use Modules\Notes\Database\Factories\NoteFactory;

class Note extends Model
{
    use HasComments,
        HasFactory,
        HasMedia,
        LazyTouchesViaPivot,
        Resourceable,
        Timelineable;

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
        'user_id' => 'int',
    ];

    /**
     * Perform any actions required after the model boots.
     */
    protected static function booted(): void
    {
        static::creating(function (Note $model) {
            $model->user_id = $model->user_id ?: auth()->id();
        });

        static::deleting(function (Note $model) {
            $model->purge();
        });
    }

    /**
     * Get all of the contacts that are assigned this note.
     */
    public function contacts(): MorphToMany
    {
        return $this->morphedByMany(\Modules\Contacts\App\Models\Contact::class, 'noteable');
    }

    /**
     * Get all of the companies that are assigned this note.
     */
    public function companies(): MorphToMany
    {
        return $this->morphedByMany(\Modules\Contacts\App\Models\Company::class, 'noteable');
    }

    /**
     * Get all of the deals that are assigned this note.
     */
    public function deals(): MorphToMany
    {
        return $this->morphedByMany(\Modules\Deals\App\Models\Deal::class, 'noteable');
    }

    /**
     * Get the note owner.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Users\App\Models\User::class);
    }

    /**
     * Get the attributes that may contain pending media.
     */
    public function textAttributesWithMedia(): string
    {
        return 'body';
    }

    /**
     * Get the timeline component for front-end.
     */
    public function getTimelineComponent(): string
    {
        return 'record-tab-timeline-note';
    }

    /**
     * Eager load the relations that are required for the front end response.
     */
    public function scopeWithCommon(Builder $query): void
    {
        $query->withCount('comments')->with('user');
    }

    /**
     * Provide the related pivot relationships to touch.
     */
    protected function relatedPivotRelationsToTouch(): array
    {
        return ['contacts', 'companies', 'deals'];
    }

    /**
     * Purge the note data.
     */
    public function purge(): void
    {
        foreach (['contacts', 'companies', 'deals'] as $relation) {
            $this->{$relation}()->withTrashed()->detach();
        }
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): NoteFactory
    {
        return NoteFactory::new();
    }
}
