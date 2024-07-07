<?php
 

namespace Modules\Contacts\App\Models;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\BroadcastableModelEventOccurred;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Modules\Activities\App\Concerns\HasActivities;
use Modules\Activities\App\Contracts\Attendeeable;
use Modules\Calls\App\Concerns\HasCalls;
use Modules\Calls\App\Models\Call;
use Modules\Contacts\App\Concerns\HasPhones;
use Modules\Contacts\App\Concerns\HasSource;
use Modules\Contacts\Database\Factories\ContactFactory;
use Modules\Core\App\Common\Media\HasMedia;
use Modules\Core\App\Common\Timeline\HasTimeline;
use Modules\Core\App\Concerns\HasAvatar;
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

class Contact extends Model implements Attendeeable, Presentable
{
    use BroadcastsEvents,
        HasActivities,
        HasAvatar,
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

    const TAGS_TYPE = 'contacts';

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
        'user.name',
        'country.name',
        'source.name',
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
        'companies' => 'contacts',
        'deals' => 'contacts',
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
        'country_id' => 'int',
        'next_activity_id' => 'int',
        'next_activity_date' => 'datetime',
    ];

    /**
     * Get all of the companies that are associated with the contact
     */
    public function companies(): MorphToMany
    {
        return $this->morphedByMany(\Modules\Contacts\App\Models\Company::class, 'contactable')
            ->withTimestamps()
            ->orderBy('contactables.created_at');
    }

    /**
     * Get all of the notes for the contact
     */
    public function notes(): MorphToMany
    {
        return $this->morphToMany(\Modules\Notes\App\Models\Note::class, 'noteable');
    }

    /**
     * Get all of the contact guests models
     */
    public function guests(): MorphMany
    {
        return $this->morphMany(\Modules\Activities\App\Models\Guest::class, 'guestable');
    }

    /**
     * Get the contact owner
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
        $firstName = $this->first_name;
        $lastName = $this->last_name;

        return trim("$firstName $lastName");
    }

    /**
     * Get the URL path
     */
    public function path(): string
    {
        return "/contacts/{$this->id}";
    }

    /**
     * Get the person email address when guest
     */
    public function getGuestEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Get the person displayable name when guest
     */
    public function getGuestDisplayName(): string
    {
        return $this->displayName();
    }

    /**
     * Get the notification that should be sent to the person when is added as guest
     *
     * @return string
     */
    public function getAttendeeNotificationClass()
    {
        return \Modules\Activities\App\Mail\ContactAttendsToActivity::class;
    }

    /**
     * Indicates whether the notification should be send to the guest
     */
    public function shouldSendAttendingNotification(Attendeeable $model): bool
    {
        return (bool) settings('send_contact_attends_to_activity_mail');
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
     * Purge the contact data.
     */
    public function purge(): void
    {
        foreach (['companies', 'emails', 'deals', 'activities', 'documents'] as $relation) {
            $this->{$relation}()->withTrashedIfUsingSoftDeletes()->detach();
        }

        $this->guests()->forceDelete();

        $this->loadMissing('notes')->notes->each(function (Note $note) {
            $note->delete();
        });
        $this->loadMissing('calls')->calls->each(function (Call $call) {
            $call->delete();
        });
    }

    /**
     * Raw concat attributes for query
     *
     * @param  array  $attributes
     * @param  string  $separator
     * @return \Illuminate\Database\Query\Expression|null
     */
    public static function nameQueryExpression($as = null)
    {
        $driver = (new static)->getConnection()->getDriverName();

        switch ($driver) {
            case 'mysql':
            case 'pgsql':
            case 'mariadb':
                return DB::raw('RTRIM(CONCAT(first_name, \' \', COALESCE(last_name, \'\')))'.($as ? ' as '.$as : ''));

                break;
            case 'sqlite':
                return DB::raw('RTRIM(first_name || \' \' || last_name)'.($as ? ' as '.$as : ''));

                break;
        }
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
    protected static function newFactory(): ContactFactory
    {
        return ContactFactory::new();
    }
}
