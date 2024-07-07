<?php
 

namespace Modules\Core\App\Models;

use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Core\App\Common\Synchronization\SyncState;
use Modules\Core\App\Concerns\HasUuid;
use Modules\Core\App\Contracts\Synchronization\SynchronizesViaWebhook;

class Synchronization extends Model
{
    use HasUuid;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_synchronized_at' => 'datetime',
        'start_sync_from' => 'datetime',
        'expires_at' => 'datetime',
        'sync_state' => SyncState::class,
    ];

    /**
     * Perform any actions required after the model boots.
     */
    protected static function booted(): void
    {
        static::creating(function (Synchronization $model) {
            $model->last_synchronized_at = now();
            $model->start_sync_from = now();
        });

        static::created(function (Synchronization $model) {
            if ($model->synchronizable->synchronizer() instanceof SynchronizesViaWebhook) {
                $model->startListeningForChanges();
            }

            $model->ping();
        });

        static::deleting(function (Synchronization $model) {
            if ($model->synchronizable->synchronizer() instanceof SynchronizesViaWebhook) {
                try {
                    $model->stopListeningForChanges();
                } catch (\Exception) {
                }
            }
        });
    }

    /**
     * Check whether sync is enabled for this synchronization instance.
     */
    public function enabled(): bool
    {
        return ! $this->isSyncDisabled() && ! $this->isSyncStopped();
    }

    /**
     * Check whether the user or system has sofly disabled the sync.
     */
    public function isSyncDisabled(): bool
    {
        return $this->sync_state === SyncState::DISABLED;
    }

    /**
     * Check whether the system disabled the sync.
     */
    public function isSyncStopped(): bool
    {
        return $this->sync_state === SyncState::STOPPED;
    }

    /**
     * Ping the snychronizable to synchronize the data.
     *
     * @return mixed
     */
    public function ping()
    {
        return $this->synchronizable->synchronize();
    }

    /**
     * Start listening for changes.
     *
     * @return mixed
     */
    public function startListeningForChanges()
    {
        return $this->synchronizable->synchronizer()->watch($this);
    }

    /**
     * Stop listening for changes.
     */
    public function stopListeningForChanges(): void
    {
        if (! $this->isSynchronizingViaWebhook()) {
            return;
        }

        $this->synchronizable->synchronizer()->unwatch($this);
    }

    /**
     * Refresh the synchronizable webhook.
     */
    public function refreshWebhook(): static
    {
        $this->stopListeningForChanges();

        // Update the UUID since the previous one has
        // already been associated to watcher.
        $this->forceFill([
            $this->uuidColumn() => $this->generateUuid(),
        ])->save();

        $this->startListeningForChanges();

        return $this;
    }

    /**
     * Get the synchronizable model.
     */
    public function synchronizable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check whether the synchronization is currently synchronizing via webhook.
     */
    public function isSynchronizingViaWebhook(): bool
    {
        return ! is_null($this->resource_id);
    }

    /**
     * Get the model uuid column name.
     */
    public function uuidColumn(): string
    {
        return 'id';
    }

    /**
     * Scope a query to only include enabled synchronizations.
     */
    public function scopeEnabled(Builder $query): void
    {
        $query->where('sync_state', SyncState::ENABLED);
    }

    /**
     * Scope a query to exclude disabled synchronizations.
     */
    public function scopeNotDisabled(Builder $query): void
    {
        $query->where('sync_state', '!=', SyncState::DISABLED);
    }

    /**
     * Apply where query where oauth account does not require authentication.
     */
    public function scopeWithoutOAuthAuthenticationRequired(Builder $query): void
    {
        $query->whereHas('synchronizable', function ($query) {
            return $query->whereHas('oAuthAccount', function ($query) {
                return $query->where('requires_auth', false);
            });
        });
    }

    /**
     * Mark the synchronization as webhook synchronizable.
     */
    public function markAsWebhookSynchronizable(string $resourceId, string|Carbon|DateTime $expiresAt): static
    {
        $this->fill([
            'resource_id' => $resourceId,
            'expires_at' => $expiresAt instanceof Carbon ? $expiresAt : Carbon::parse($expiresAt),
        ])->save();

        return $this;
    }

    /**
     * Unmark the synchronization as webhook synchronizable.
     */
    public function unmarkAsWebhookSynchronizable(): static
    {
        $this->fill(['resource_id' => null, 'expires_at' => null])->save();

        return $this;
    }

    /**
     * Set the sync state.
     */
    public function setSyncState(SyncState $state, ?string $comment = null): static
    {
        $this->fill(['sync_state' => $state, 'sync_state_comment' => $comment])->save();

        return $this;
    }

    /**
     * Enable synchronization.
     */
    public function enableSync(): static
    {
        $this->loadMissing('synchronizable');

        // When enabling synchronization, we will try again to re-configure the webhook
        // and catch any URL related errors, if any errors, the sync won't be enabled
        if ($this->synchronizable->synchronizer() instanceof SynchronizesViaWebhook) {
            $this->refreshWebhook();
        }

        $this->setSyncState(SyncState::ENABLED);

        return $this;
    }

    /**
     * Disable synchronization.
     */
    public function disableSync(?string $comment = null): static
    {
        if ($this->isSynchronizingViaWebhook()) {
            $this->stopListeningForChanges();
        }

        $this->setSyncState(SyncState::DISABLED, $comment);

        $this->fill(['token' => null])->save();

        return $this;
    }

    /**
     * Stop synchronization.
     */
    public function stopSync(?string $comment = null): static
    {
        if ($this->isSynchronizingViaWebhook()) {
            $this->stopListeningForChanges();
        }

        $this->setSyncState(SyncState::STOPPED, $comment);

        $this->fill(['token' => null])->save();

        return $this;
    }

    /**
     * Scope a query to only include synchronization for period sync.
     */
    public function scopeEnabledPeriodicable(Builder $query): void
    {
        $query->withoutOAuthAuthenticationRequired()
            ->whereNull('resource_id')
            ->enabled();
    }

    /**
     * Update the last synchronized date.
     */
    public function updateLastSyncDate(array $extra = []): static
    {
        Model::withoutTimestamps(
            fn () => $this->fill(array_merge(['last_synchronized_at' => now()], $extra))->save()
        );

        return $this;
    }
}
