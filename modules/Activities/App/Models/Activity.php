<?php
 

namespace Modules\Activities\App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Activities\App\Concerns\HasGuests;
use Modules\Activities\App\Jobs\DeleteCalendarEvent;
use Modules\Activities\App\Models\Calendar as CalendarModel;
use Modules\Activities\Database\Factories\ActivityFactory;
use Modules\Comments\App\Concerns\HasComments;
use Modules\Contacts\App\Models\Company;
use Modules\Contacts\App\Models\Contact;
use Modules\Core\App\Common\Media\HasMedia;
use Modules\Core\App\Common\Timeline\Timelineable;
use Modules\Core\App\Concerns\HasCreator;
use Modules\Core\App\Concerns\LazyTouchesViaPivot;
use Modules\Core\App\Concerns\Prunable;
use Modules\Core\App\Contracts\Calendar\DisplaysOnCalendar;
use Modules\Core\App\Contracts\Presentable;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Resource\Import\Import;
use Modules\Core\App\Resource\Resourceable;
use Modules\Core\App\Support\Carbon;
use Modules\Deals\App\Models\Deal;
use Modules\Users\App\Models\User;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;
use Spatie\IcalendarGenerator\Enums\ParticipationStatus;

class Activity extends Model implements DisplaysOnCalendar, Presentable
{
    use HasComments,
        HasCreator,
        HasFactory,
        HasGuests,
        HasMedia,
        LazyTouchesViaPivot,
        Prunable,
        Resourceable,
        SoftDeletes,
        Timelineable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title', 'description', 'note',
        'due_date', 'due_time', 'end_time', 'end_date',
        'user_id', 'reminder_minutes_before',
        'activity_type_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'activity_type_id' => 'int',
        'reminder_at' => 'datetime',
        'completed_at' => 'datetime',
        'owner_assigned_date' => 'datetime',
        'reminded_at' => 'datetime',
        'reminder_minutes_before' => 'int',
        'user_id' => 'int',
        'created_by' => 'int',
    ];

    /**
     * Indicates whether the activity should be created on the calendar.
     */
    public bool $calendarable = true;

    public static $preUpdateUser = null;

    /**
     * Determine the reminder at date value
     *
     * @param  \Modules\Activities\App\Models\Activity  $model
     * @return null|\Carbon\Carbon
     */
    public static function determineReminderAtDate($model)
    {
        if (is_null($model->reminder_minutes_before)) {
            return;
        }

        if (! $model->due_time) {
            return Carbon::asCurrentTimezone($model->due_date.' 00:00:00', $model->user)
                ->subMinutes($model->reminder_minutes_before)
                ->inAppTimezone();
        }

        return Carbon::parse($model->full_due_date)->subMinutes($model->reminder_minutes_before);
    }

    /**
     * Check whether the activity is synchronized to a calendar
     */
    public function isSynchronizedToCalendar(?CalendarModel $calendar): bool
    {
        if (! $calendar) {
            return false;
        }

        return ! is_null($this->latestSynchronization($calendar));
    }

    /**
     * Check whether the current activity type can be synchronized to calendar
     */
    public function typeCanBeSynchronizedToCalendar(): bool
    {
        return in_array($this->activity_type_id, $this->user->calendar->activity_types);
    }

    /**
     * Get the activity latest synchronization for the given calendar
     */
    public function latestSynchronization(?CalendarModel $calendar = null): ?CalendarModel
    {
        return $this->synchronizations->where(
            'id', // calendar id from calendars table
            ($calendar ?? $this->user->calendar)->getKey()
        )[0] ?? null;
    }

    /**
     * Get the activity calendar synchronizations
     */
    public function synchronizations(): BelongsToMany
    {
        return $this->belongsToMany(\Modules\Activities\App\Models\Calendar::class, 'activity_calendar_sync')
            ->withPivot(['event_id', 'synchronized_at'])
            ->latest('synchronized_at');
    }

    /**
     * Activity has type
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(\Modules\Activities\App\Models\ActivityType::class, 'activity_type_id');
    }

    /**
     * Get the activity owner
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the ICS file file name when downloaded
     */
    public function icsFilename(): string
    {
        return Str::slug($this->type->name).'-'.Carbon::parse($this->full_due_date)->format('Y-m-d-H:i:s');
    }

    /**
     * Generate ICS instance
     *
     * @return \Spatie\IcalendarGenerator\Components\Calendar
     */
    public function generateICSInstance()
    {
        $event = Event::create()
            ->name($this->title)
            ->description($this->description ?? '')
            ->createdAt($this->created_at)
            ->startsAt(new \DateTime($this->full_due_date))
            ->endsAt(new \DateTime($this->full_end_date))
            ->withoutTimezone()
            ->organizer($this->creator->email, $this->creator->name);

        if ($this->isAllDay()) {
            $event->fullDay();
        }

        $this->load('guests.guestable');

        $this->guests->reject(function ($model) {
            return ! $model->guestable->getGuestEmail();
        })->each(function ($model) use ($event) {
            $event->attendee(
                $model->guestable->getGuestEmail(),
                $model->guestable->getGuestDisplayName(),
                ParticipationStatus::accepted() // not working, still show in thunderbird statuses to accept
            );
        });

        return Calendar::create()
            ->withoutAutoTimezoneComponents()
            ->event($event)
            ->productIdentifier(config('app.name'));
    }

    /**
     * Indicates whether the activity owner is reminded
     */
    public function isReminded(): Attribute
    {
        return Attribute::get(
            fn () => ! is_null($this->reminded_at)
        );
    }

    /**
     *  Indicates whether the activity is completed
     */
    public function isCompleted(): Attribute
    {
        return Attribute::get(
            fn () => ! is_null($this->completed_at)
        );
    }

    /**
     * Get all of the contacts that are assigned this activity.
     */
    public function contacts(): MorphToMany
    {
        return $this->morphedByMany(\Modules\Contacts\App\Models\Contact::class, 'activityable');
    }

    /**
     * Get all of the companies that are assigned this activity.
     */
    public function companies(): MorphToMany
    {
        return $this->morphedByMany(\Modules\Contacts\App\Models\Company::class, 'activityable');
    }

    /**
     * Get all of the deals that are assigned this activity.
     */
    public function deals(): MorphToMany
    {
        return $this->morphedByMany(\Modules\Deals\App\Models\Deal::class, 'activityable');
    }

    /**
     * Indicates whether the activity is all day event
     */
    public function isAllDay(): bool
    {
        return is_null($this->due_time) && is_null($this->end_time);
    }

    /**
     * Get the calendar event start date
     */
    public function getCalendarStartDate(): string
    {
        $instance = Carbon::inUserTimezone($this->full_due_date);

        return $this->due_time ? $instance->format('Y-m-d\TH:i:s') : $instance->format('Y-m-d');
    }

    /**
     * Get the calendar event end date
     */
    public function getCalendarEndDate(): string
    {
        $instance = Carbon::inUserTimezone($this->full_end_date);

        return $this->end_time ? $instance->format('Y-m-d\TH:i:s') : $instance->format('Y-m-d');
    }

    /**
     * Get the displayable title for the calendar
     */
    public function getCalendarTitle(): string
    {
        return $this->title;
    }

    /**
     * Get the calendar start date column name for query
     */
    public static function getCalendarStartColumnName(): Expression
    {
        return static::dueDateQueryExpression();
    }

    /**
     * Get the calendar end date column name for query
     */
    public static function getCalendarEndColumnName(): Expression
    {
        return static::dateTimeExpression('end_date', 'end_time');
    }

    /**
     * Get the calendar additional properties.
     */
    public function getCalendarExtendedProps(): array
    {
        return [
            'type' => $this->type,
        ];
    }

    /**
     * Get the model display name
     */
    public function displayName(): string
    {
        return $this->title;
    }

    /**
     * Get the URL path
     */
    public function path(): string
    {
        return "/activities/{$this->id}";
    }

    /**
     * Get isDue attribute
     */
    public function isDue(): Attribute
    {
        return Attribute::get(
            fn () => ! $this->is_completed && $this->full_due_date < date('Y-m-d H:i:s')
        );
    }

    /**
     * Get the attributes that may contain pending media.
     */
    public function textAttributesWithMedia(): array
    {
        return ['description', 'note'];
    }

    /**
     * Get the full due date in UTC including the time (if has)
     */
    public function fullDueDate(): Attribute
    {
        return Attribute::get(function () {
            $dueDate = $this->asDateTime($this->due_date);

            return $this->due_time ?
            $dueDate->format('Y-m-d').' '.$this->due_time :
            $dueDate->format('Y-m-d');
        });
    }

    /**
     * Get the full end date in UTC including the time (if has)
     */
    public function fullEndDate(): Attribute
    {
        return Attribute::get(function () {
            $endDate = $this->asDateTime($this->end_date);

            return $this->end_time ?
                $endDate->format('Y-m-d').' '.$this->end_time :
                $endDate->format('Y-m-d');
        });
    }

    /**
     * Create due date expression for query
     */
    public static function dueDateQueryExpression(?string $as = null): ?Expression
    {
        return static::dateTimeExpression('due_date', 'due_time', $as);
    }

    /**
     * Create date time expression for querying
     */
    public static function dateTimeExpression(string $dateField, string $timeField, ?string $as = null): ?Expression
    {
        $driver = (new static)->getConnection()->getDriverName();

        switch ($driver) {
            case 'mysql':
            case 'pgsql':
            case 'mariadb':
                return DB::raw('RTRIM(CONCAT('.$dateField.', \' \', COALESCE('.$timeField.', \'\')))'.($as ? ' as '.$as : ''));

                break;
            case 'sqlite':
                return DB::raw('RTRIM('.$dateField.' || \' \' || COALESCE('.$timeField.', \'\'))'.($as ? ' as '.$as : ''));

                break;
        }
    }

    /**
     * Get the timeline component for front-end
     */
    public function getTimelineComponent(): string
    {
        return 'record-tab-timeline-activity';
    }

    /**
     * Scope a query to include incomplete and in future activities.
     */
    public function scopeIncompleteAndInFuture(Builder $query): Builder
    {
        return $query->incomplete()->where(static::dueDateQueryExpression(), '>=', Carbon::asAppTimezone());
    }

    /**
     * Scope a query to include overdue activities.
     *
     * @param  string  $operator  <= for overdue > for not overdue
     */
    public function scopeOverdue(Builder $query, $operator = '<='): Builder
    {
        return $query->incomplete()->where(
            static::dueDateQueryExpression(),
            $operator,
            Carbon::asAppTimezone()
        );
    }

    /**
     * Scope a query to include incompleted activities.
     *
     * @param  string  $condition
     */
    public function scopeIncomplete(Builder $query, $condition = 'and'): Builder
    {
        return $query->whereNull('completed_at', $condition);
    }

    /**
     * Scope a query to include completed activities.
     *
     * @param  string  $condition
     */
    public function scopeCompleted(Builder $query, $condition = 'and'): Builder
    {
        return $query->whereNotNull('completed_at', $condition);
    }

    /**
     * Scope a query to only include activities that are due today.
     */
    public function scopeDueToday(Builder $query): void
    {
        $now = Carbon::asCurrentTimezone();

        $query->whereBetween(
            static::dueDateQueryExpression(),
            [$now->copy()->startOfDay(), $now->endOfDay()]
        )->incomplete();
    }

    /**
     * Scope a query to only include upcoming activities.
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where(static::dueDateQueryExpression(), '>', Carbon::asAppTimezone());
    }

    /**
     * Scope a query to only include activities due for reminder.
     */
    public function scopeReminderable(Builder $query): void
    {
        $query->notReminded()
            ->incomplete()
            ->whereNotNull('reminder_at')
            ->where('reminder_at', '<=', Carbon::asAppTimezone());
    }

    /**
     * Scope a query to only include activities that no reminder is sent.
     */
    public function scopeNotReminded(Builder $query): void
    {
        $query->whereNull('reminded_at');
    }

    /**
     * Mark the activity as complete.
     */
    public function markAsComplete(): static
    {
        if (! $this->is_completed) {
            $this->completed_at = now();
            $this->save();
        }

        return $this;
    }

    /**
     * Mark the activity as incomplete.
     */
    public function markAsIncomplete(): static
    {
        $this->completed_at = null;
        $this->save();

        return $this;
    }

    /**
     * Tap the calendar request query
     */
    public function tapCalendarQuery(Builder $query, Request $request): void
    {
        $user = $request->user();

        // Show only incomplete activities on calendar.
        $query->incomplete();

        if ($user->cant('view all activities')) {
            $query->where('user_id', $user->getKey());
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('activity_type_id')) {
            $query->where('activity_type_id', $request->integer('activity_type_id'));
        }

        $query->with('type');
    }

    /**
     * Tap the calendar request date query
     */
    public function tapCalendarDateQuery(Builder $query, string|Expression $startColumn, string|Expression $endColumn, Request $request): void
    {
        $query->orWhere(function ($query) use ($request) {
            $startInUserTimezone = Carbon::parse($request->start_date)->tz($request->user()->timezone);
            $endInUserTimezone = Carbon::parse($request->end_date)->tz($request->user()->timezone);

            $startFormatted = $startInUserTimezone->format('Y-m-d');
            $endFormatted = $endInUserTimezone->format('Y-m-d');

            // https://stackoverflow.com/questions/17014066/mysql-query-to-select-events-between-start-end-date
            $spanRaw = '? BETWEEN due_date AND end_date';

            return $query->whereRaw(
                "CASE
                    WHEN due_time IS NULL AND end_time IS NULL THEN due_date BETWEEN
                    ? AND ? OR {$spanRaw}
                    WHEN due_time IS NOT NULL AND end_time IS NULL THEN due_date
                    BETWEEN ? AND ? OR {$spanRaw}
                END",
                [$startFormatted, $endFormatted, $startFormatted, $request->start_date, $endFormatted, $startFormatted]
            );
        });
    }

    /**
     * Add activity synchronization data
     */
    public function addSynchronization(string|int $eventId, int $calendarId, array $attributes): void
    {
        $this->synchronizations()->attach($calendarId, [
            'synchronized_at' => now(),
            'event_id' => $eventId,
        ] + $attributes);
    }

    /**
     * Update activity synchronization data
     */
    public function updateSynchronization(string|int $eventId, int $calendarId, array $attributes): void
    {
        $this->synchronizations()->where('event_id', $eventId)
            ->where('activity_calendar_sync.calendar_id', $calendarId)
            ->update($attributes);
    }

    /**
     * Delete the activity synchronization data
     */
    public function deleteSynchronization(string|int $eventId, int $calendarId): void
    {
        $this->synchronizations()->where('event_id', $eventId)->detach($calendarId);
    }

    /**
     * Eager load the relations that are required for the front end response.
     */
    public function scopeByEventSyncId(Builder $query, string|int $eventId): void
    {
        $query->withTrashed()->whereHas('synchronizations', fn ($query) => $query->where('event_id', $eventId));
    }

    /**
     * Eager load the relations that are required for the front end response.
     */
    public function scopeWithCommon(Builder $query): void
    {
        $query->withCount('comments')->with('media');
    }

    public function canSyncToCalendar(): bool
    {
        if (Import::$running) {
            return false;
        }

        return $this->calendarable && $this->user->canSyncToCalendar() && $this->typeCanBeSynchronizedToCalendar();
    }

    /**
     * Delete the activity from the synced calendar.
     */
    public function deleteFromCalendar(?User $user = null)
    {
        $user = $user ?: $this->user;

        if (! $this->isSynchronizedToCalendar($user->calendar)) {
            return;
        }

        $eventId = $this->latestSynchronization($user->calendar)->pivot->event_id;
        $activityId = $this->getKey();

        DeleteCalendarEvent::dispatch($user->calendar, $activityId, $eventId);
    }

    /**
     * Provide the related pivot relationships to touch.
     */
    protected function relatedPivotRelationsToTouch(): array
    {
        return ['contacts', 'companies', 'deals'];
    }

    /**
     * Purge the activity data
     */
    public function purge(bool $fromCalendar = true): void
    {
        if ($fromCalendar) {
            $this->deleteFromCalendar();
        }

        foreach (['contacts', 'companies', 'deals'] as $relation) {
            $this->{$relation}()->withTrashed()->detach();
        }

        $this->loadMissing('guests')->guests->each(function ($guest) {
            $guest->activities()->withTrashed()->detach();
            $guest->delete();
        });

        foreach ([Contact::class, Company::class, Deal::class] as $model) {
            $model::withoutTimestamps(function () use ($model) {
                $model::withTrashed()
                    ->where('next_activity_id', $this->getKey())
                    ->update(['next_activity_id' => null, 'next_activity_date' => null]);
            });
        }
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ActivityFactory
    {
        return ActivityFactory::new();
    }
}
