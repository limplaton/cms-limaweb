<?php
 

namespace Modules\Deals\App\Models;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\BroadcastableModelEventOccurred;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Modules\Activities\App\Concerns\HasActivities;
use Modules\Billable\App\Concerns\HasProducts;
use Modules\Calls\App\Concerns\HasCalls;
use Modules\Calls\App\Models\Call;
use Modules\Core\App\Common\Media\HasMedia;
use Modules\Core\App\Common\Timeline\HasTimeline;
use Modules\Core\App\Concerns\HasCreator;
use Modules\Core\App\Concerns\HasTags;
use Modules\Core\App\Concerns\HasUuid;
use Modules\Core\App\Concerns\LazyTouchesViaPivot;
use Modules\Core\App\Concerns\Prunable;
use Modules\Core\App\Contracts\Presentable;
use Modules\Core\App\Facades\ChangeLogger;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Resource\Resourceable;
use Modules\Core\App\Workflow\HasWorkflowTriggers;
use Modules\Deals\App\Enums\DealStatus;
use Modules\Deals\Database\Factories\DealFactory;
use Modules\Documents\App\Concerns\HasDocuments;
use Modules\MailClient\App\Concerns\HasEmails;
use Modules\Notes\App\Models\Note;

class Deal extends Model implements Presentable
{
    use BroadcastsEvents,
        HasActivities,
        HasCalls,
        HasCreator,
        HasDocuments,
        HasEmails,
        HasFactory,
        HasMedia,
        HasProducts,
        HasTags,
        HasTimeline,
        HasUuid,
        HasWorkflowTriggers,
        LazyTouchesViaPivot,
        Prunable,
        Resourceable,
        SoftDeletes;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = [
        'created_by',
        'created_at',
        'updated_at',
        'owner_assigned_date',
        'next_activity_id',
        'stage_changed_date',
        'uuid',
    ];

    const TAGS_TYPE = 'deals';

    /**
     * Attributes and relations to log changelog for the model
     *
     * @var array
     */
    protected static $changelogAttributes = [
        '*',
        'user.name',
        'pipeline.name',
    ];

    /**
     * Exclude attributes from the changelog
     *
     * @var array
     */
    protected static $changelogAttributeToIgnore = [
        'updated_at',
        'created_at',
        'created_by',
        'owner_assigned_date',
        'stage_changed_date',
        'swatch_color',
        'board_order',
        'status',
        'won_date',
        'lost_date',
        'lost_reason',
        'next_activity_id',
        'deleted_at',
        // Stage change are handled via custom pivot log events
        'stage_id',
    ];

    /**
     * Provides the relationships for the pivot logger
     *
     * [ 'main' => 'reverse']
     *
     * @return array
     */
    protected static $logPivotEventsOn = [
        'companies' => 'deals',
        'contacts' => 'deals',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expected_close_date' => 'date',
        'stage_changed_date' => 'datetime',
        'owner_assigned_date' => 'datetime',
        'won_date' => 'datetime',
        'lost_date' => 'datetime',
        'status' => DealStatus::class,
        'amount' => 'decimal:3',
        'stage_id' => 'int',
        'pipeline_id' => 'int',
        'user_id' => 'int',
        'board_order' => 'int',
        'created_by' => 'int',
        'web_form_id' => 'int',
        'next_activity_id' => 'int',
        'next_activity_date' => 'datetime',
    ];

    /**
     * Indicates whether the deal "updating" and "updated" events are triggered via the board
     */
    public bool $boardFiresEvents = false;

    /**
     * Indicates whether to broadcast to the current user when the model is updated
     */
    public bool $broadcastToCurrentUser = false;

    /**
     * Check whether the falls behind the expected close date
     */
    public function fallsBehindExpectedCloseDate(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->expected_close_date || ! $this->isOpen()) {
                return false;
            }

            return $this->expected_close_date->isPast();
        });
    }

    /**
     * Determine whether the deal is lost.
     */
    public function isLost(): bool
    {
        return $this->status === DealStatus::lost;
    }

    /**
     * Determine whether the deal is won.
     */
    public function isWon()
    {
        return $this->status === DealStatus::won;
    }

    /**
     * Determine whether the deal is open.
     */
    public function isOpen(): bool
    {
        return $this->status === DealStatus::open;
    }

    /**
     * Check whether the status for the deal can be changed.
     */
    public function isStatusLocked(DealStatus $status): bool
    {
        // If it's the same status, it's not locked.
        if ($this->status === $status) {
            return false;
        }

        if ($this->isLost() || $this->isWon()) {
            return $status !== DealStatus::open;
        }

        return false;
    }

    /**
     * Fill the deal status.
     */
    public function fillStatus(DealStatus $status, ?string $lostReason = null): static
    {
        $this->status = $status;

        if ($status === DealStatus::lost) {
            $this->lost_reason = $lostReason;
        }

        return $this;
    }

    /**
     * Mark the deal as lost
     */
    public function markAsLost(?string $reason = null): static
    {
        $this->fillStatus(DealStatus::lost, $reason)->save();

        return $this;
    }

    /**
     * Mark the deal as wont
     */
    public function markAsWon(): static
    {
        $this->fillStatus(DealStatus::won)->save();

        return $this;
    }

    /**
     * Mark the deal as open
     */
    public function markAsOpen(): static
    {
        $this->fillStatus(DealStatus::open)->save();

        return $this;
    }

    /**
     * Log status change activity for the deal
     *
     * @return \Modules\Core\App\Models\Changelog
     */
    public function logStatusChangeActivity(string $type, array $attrs = [])
    {
        return ChangeLogger::onModel($this, [
            'lang' => [
                'key' => 'deals::deal.timeline.'.$type,
                'attrs' => $attrs,
            ],
        ])->log();
    }

    /**
     * Get the pipeline that belongs to the deal.
     */
    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(\Modules\Deals\App\Models\Pipeline::class);
    }

    /**
     * Get the stage that belongs to the deal.
     */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    /**
     * Get the stages history the deal has been.
     */
    public function stagesHistory(): BelongsToMany
    {
        return $this->belongsToMany(Stage::class, 'deal_stages_history')
            ->withPivot(['id', 'entered_at', 'left_at'])
            ->using(\Modules\Deals\App\Models\StageHistory::class)
            ->orderBy('entered_at', 'desc')
            ->as('history');
    }

    /**
     * Get the stages history with time in
     */
    public function timeInStages(): Collection
    {
        return $this->stagesHistory->groupBy('id')->map(function ($stages) {
            return $stages->reduce(function ($carry, $stage) {
                // If left_at is null, is using the current time
                $carry += $stage->history->entered_at->diffInSeconds($stage->history->left_at);

                return $carry;
            }, 0);
        });
    }

    /**
     * Start start history from the deal current stage
     */
    public function startStageHistory(): static
    {
        $this->recordStageHistory($this->stage_id);

        return $this;
    }

    /**
     * Get the deal last stage history
     */
    public function lastStageHistory(): ?Stage
    {
        return $this->stagesHistory()->first();
    }

    /**
     * Stop the deal last stage timing
     */
    public function stopLastStageTiming(): static
    {
        $latest = $this->lastStageHistory();

        if ($latest && is_null($latest['history']['left_at'])) {
            $this->stagesHistory()
                ->wherePivot('id', $latest->history->id)
                ->updateExistingPivot($latest->history->stage_id, ['left_at' => now()]);
        }

        return $this;
    }

    /**
     * Record stage history
     */
    public function recordStageHistory(int $stageId): static
    {
        $this->stopLastStageTiming();
        $this->stagesHistory()->attach($stageId, ['entered_at' => now()]);

        return $this;
    }

    /**
     * Get all of the contacts that are associated with the deal
     */
    public function contacts(): MorphToMany
    {
        return $this->morphedByMany(\Modules\Contacts\App\Models\Contact::class, 'dealable')
            ->withTimestamps()
            ->orderBy('dealables.created_at');
    }

    /**
     * Get all of the companies that are associated with the deal
     */
    public function companies(): MorphToMany
    {
        return $this->morphedByMany(\Modules\Contacts\App\Models\Company::class, 'dealable')
            ->withTimestamps()
            ->orderBy('dealables.created_at');
    }

    /**
     * Get all of the notes for the deal
     */
    public function notes(): MorphToMany
    {
        return $this->morphToMany(\Modules\Notes\App\Models\Note::class, 'noteable');
    }

    /**
     * Get the deal owner
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Users\App\Models\User::class);
    }

    /**
     * Get the model display name
     */
    public function displayName(): string
    {
        return $this->name;
    }

    /**
     * Get the URL path
     */
    public function path(): string
    {
        return "/deals/{$this->id}";
    }

    /**
     * Provide the total column to be updated whenever the billable is updated
     */
    public function totalColumn(): string
    {
        return 'amount';
    }

    /**
     * Get the channels that model events should broadcast on.
     *
     * @param  string  $event
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn($event)
    {
        // We will broadcast only when stage is changed when the update
        // comes from the custom broadcast event in "BoardUpdater"
        if ($this->boardFiresEvents && ! $this->isDirty('stage_id')) {
            return [];
        }

        // Currently only the updated event is used
        return match ($event) {
            'updated' => [new PrivateChannel($this)],
            default => null,
        };
    }

    /**
     * Broadcast to current user when updated
     */
    public function broadcastToCurrentUser(): static
    {
        $this->broadcastToCurrentUser = true;

        return $this;
    }

    /**
     * Create a new broadcastable model event for the model.
     *
     * @return \Illuminate\Database\Eloquent\BroadcastableModelEventOccurred
     */
    protected function newBroadcastableEvent(string $event)
    {
        $instance = (new BroadcastableModelEventOccurred(
            $this,
            $event
        ));

        if ($this->broadcastToCurrentUser) {
            return $instance;
        }

        return $instance->dontBroadcastToCurrentUser();
    }

    /**
     * Get the data to broadcast for the model.
     *
     * @param  string  $event
     */
    public function broadcastWith($event): array
    {
        return [];
    }

    /**
     * Scope a query to only include open deals.
     */
    public function scopeWon(Builder $query): void
    {
        $query->where('status', DealStatus::won);
    }

    /**
     * Scope a query to only include open deals.
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', DealStatus::open);
    }

    /**
     * Scope a query to only include lost deals.
     */
    public function scopeLost(Builder $query): void
    {
        $query->where('status', DealStatus::lost);
    }

    /**
     * Scope a query to only include closed deals.
     */
    public function scopeClosed(Builder $query): void
    {
        $query->where(function (Builder $query) {
            $query->where('status', DealStatus::won)->orWhere('status', DealStatus::lost);
        });
    }

    /**
     * Scope a query to only include only deals of the given pipeline.
     */
    public function scopeOfPipeline(Builder $query, Pipeline|int $pipeline): void
    {
        $query->where('pipeline_id', is_int($pipeline) ? $pipeline : $pipeline->getKey());
    }

    /**
     * Eager load the relations that are required for the front end response.
     */
    public function scopeWithCommon(Builder $query): void
    {
        $query
            ->withCount([
                'products' => fn (Builder $query) => $query->withoutGlobalScope('displayOrder'),
            ])
            ->with(['stagesHistory']);
    }

    /**
     * Purge the model data.
     */
    public function purge(): void
    {
        foreach (['emails', 'contacts', 'companies', 'activities', 'documents'] as $relation) {
            $this->{$relation}()->withTrashedIfUsingSoftDeletes()->detach();
        }

        $this->loadMissing('billable');

        if ($this->billable) {
            $this->billable->delete();
        }

        $this->loadMissing('notes')->notes->each(function (Note $note) {
            $note->delete();
        });
        $this->loadMissing('calls')->calls->each(function (Call $call) {
            $call->delete();
        });
    }

    /**
     * Provide the related pivot relationships to touch.
     */
    protected function relatedPivotRelationsToTouch(): array
    {
        return ['companies', 'deals'];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): DealFactory
    {
        return DealFactory::new();
    }
}
