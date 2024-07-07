<?php
 

namespace Modules\Contacts\App\Models;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\BroadcastableModelEventOccurred;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Activities\App\Concerns\HasActivities;
use Modules\Calls\App\Concerns\HasCalls;
use Modules\Calls\App\Models\Call;
use Modules\Contacts\App\Concerns\HasPhones;
use Modules\Contacts\App\Concerns\HasSource;
use Modules\Contacts\Database\Factories\CompanyFactory;
use Modules\Core\App\Common\Media\HasMedia;
use Modules\Core\App\Common\Timeline\HasTimeline;
use Modules\Core\App\Concerns\HasCountry;
use Modules\Core\App\Concerns\HasCreator;
use Modules\Core\App\Concerns\HasTags;
use Modules\Core\App\Concerns\HasUuid;
use Modules\Core\App\Concerns\LazyTouchesViaPivot;
use Modules\Core\App\Concerns\Prunable;
use Modules\Core\App\Contracts\Presentable;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Resource\Resourceable;
use Modules\Core\App\Workflow\HasWorkflowTriggers;
use Modules\Deals\App\Concerns\HasDeals;
use Modules\Documents\App\Concerns\HasDocuments;
use Modules\MailClient\App\Concerns\HasEmails;
use Modules\Notes\App\Models\Note;

class Company extends Model implements Presentable
{
    use BroadcastsEvents,
        HasActivities,
        HasCalls,
        HasCountry,
        HasCreator,
        HasDeals,
        HasDocuments,
        HasEmails,
        HasFactory,
        HasMedia,
        HasPhones,
        HasSource,
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
        'uuid',
    ];

    /**
     * Attributes and relations to log changelog for the model
     *
     * @var array
     */
    protected static $changelogAttributes = [
        '*',
        'country.name',
        'parent.name',
        'source.name',
        'user.name',
        'industry.name',
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
        'next_activity_id',
        'deleted_at',
    ];

    /**
     * Provides the relationships for the pivot logger
     *
     * [ 'main' => 'reverse']
     *
     * @return array
     */
    protected static $logPivotEventsOn = [
        'contacts' => 'companies',
        'deals' => 'companies',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'owner_assigned_date' => 'datetime',
        'user_id' => 'int',
        'created_by' => 'int',
        'source_id' => 'int',
        'industry_id' => 'int',
        'parent_company_id' => 'int',
        'country_id' => 'int',
        'next_activity_id' => 'int',
        'next_activity_date' => 'datetime',
    ];

    /**
     * Get the parent company
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(\Modules\Contacts\App\Models\Company::class, 'parent_company_id');
    }

    /**
     * Get all of the company parent companies
     */
    public function parents(): HasMany
    {
        return $this->hasMany(\Modules\Contacts\App\Models\Company::class, 'parent_company_id');
    }

    /**
     * Get all of the contacts that are associated with the company
     */
    public function contacts(): MorphToMany
    {
        return $this->morphToMany(\Modules\Contacts\App\Models\Contact::class, 'contactable')
            ->withTimestamps()
            ->orderBy('contactables.created_at');
    }

    /**
     * Get all of the notes for the company
     */
    public function notes(): MorphToMany
    {
        return $this->morphToMany(\Modules\Notes\App\Models\Note::class, 'noteable');
    }

    /**
     * Get the company owner
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Users\App\Models\User::class);
    }

    /**
     * Get the company industry
     */
    public function industry(): BelongsTo
    {
        return $this->belongsTo(\Modules\Contacts\App\Models\Industry::class);
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
        return "/companies/{$this->id}";
    }

    /**
     * Get the channels that model events should broadcast on.
     *
     * @param  string  $event
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn($event)
    {
        // Currently only the updated event is used
        return match ($event) {
            'updated' => [new PrivateChannel($this)],
            default => null,
        };
    }

    /**
     * Create a new broadcastable model event for the model.
     *
     * @return \Illuminate\Database\Eloquent\BroadcastableModelEventOccurred
     */
    protected function newBroadcastableEvent(string $event)
    {
        return (new BroadcastableModelEventOccurred(
            $this,
            $event
        ))->dontBroadcastToCurrentUser();
    }

    /**
     * Purge the company data.
     */
    public function purge(): void
    {
        foreach (['contacts', 'emails', 'deals', 'activities', 'documents'] as $relation) {
            $this->{$relation}()->withTrashedIfUsingSoftDeletes()->detach();
        }

        $this->parents()->update(['parent_company_id' => null]);

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
        return ['contacts', 'deals'];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }
}
