<?php
 

namespace Modules\Activities\App\Resources;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Modules\Activities\App\Criteria\ViewAuthorizedActivitiesCriteria;
use Modules\Activities\App\Fields\ActivityDueDate;
use Modules\Activities\App\Fields\ActivityEndDate;
use Modules\Activities\App\Fields\ActivityType as ActivityTypeField;
use Modules\Activities\App\Fields\GuestsSelect;
use Modules\Activities\App\Filters\DueThisWeekActivities;
use Modules\Activities\App\Filters\DueTodayActivities;
use Modules\Activities\App\Filters\OpenActivities;
use Modules\Activities\App\Filters\OverdueActivities;
use Modules\Activities\App\Http\Resources\ActivityResource;
use Modules\Activities\App\Models\ActivityType;
use Modules\Comments\App\Contracts\HasComments;
use Modules\Comments\App\Contracts\PipesComments;
use Modules\Contacts\App\Fields\Companies;
use Modules\Contacts\App\Fields\Contacts;
use Modules\Core\App\Actions\DeleteAction;
use Modules\Core\App\Contracts\Resources\Exportable;
use Modules\Core\App\Contracts\Resources\HasOperations;
use Modules\Core\App\Contracts\Resources\Importable;
use Modules\Core\App\Contracts\Resources\Mediable;
use Modules\Core\App\Contracts\Resources\Tableable;
use Modules\Core\App\Facades\Permissions;
use Modules\Core\App\Fields\BelongsTo;
use Modules\Core\App\Fields\Boolean;
use Modules\Core\App\Fields\DateTime;
use Modules\Core\App\Fields\Editor;
use Modules\Core\App\Fields\Heading;
use Modules\Core\App\Fields\Reminder;
use Modules\Core\App\Fields\Text;
use Modules\Core\App\Fields\User;
use Modules\Core\App\Filters\DateTime as DateTimeFilter;
use Modules\Core\App\Filters\Radio as RadioFilter;
use Modules\Core\App\Filters\Select as SelectFilter;
use Modules\Core\App\Filters\Text as TextFilter;
use Modules\Core\App\Http\Requests\ActionRequest;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Menu\MenuItem;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Models\PinnedTimelineSubject;
use Modules\Core\App\QueryBuilder\Parser;
use Modules\Core\App\Resource\Resource;
use Modules\Core\App\Rules\StringRule;
use Modules\Core\App\Settings\SettingsMenuItem;
use Modules\Core\App\Table\Column;
use Modules\Core\App\Table\Table;
use Modules\Deals\App\Fields\Deals;
use Modules\Users\App\Filters\UserFilter;
use Modules\Users\App\Models\User as UserModel;

class Activity extends Resource implements Exportable, HasComments, HasOperations, Importable, Mediable, PipesComments, Tableable
{
    /**
     * The column the records should be default ordered by when retrieving
     */
    public static string $orderBy = 'title';

    /**
     * Indicates whether the resource is globally searchable
     */
    public static bool $globallySearchable = true;

    /**
     * Indicates the global search action. (presentable, float)
     */
    public static string $globalSearchAction = 'float';

    /**
     * The resource displayable icon.
     */
    public static ?string $icon = 'Calendar';

    /**
     * The model the resource is related to
     */
    public static string $model = 'Modules\Activities\App\Models\Activity';

    /**
     * Get the menu items for the resource
     */
    public function menu(): array
    {
        return [
            MenuItem::make(static::label(), '/activities', static::$icon)
                ->position(10)
                ->inQuickCreate()
                ->keyboardShortcutChar('A'),
        ];
    }

    /**
     * Provide the resource table class instance.
     */
    public function table(Builder $query, ResourceRequest $request): Table
    {
        if ($request->filled('activity_type_id') && is_numeric($request->activity_type_id)) {
            $query->where('activity_type_id', $request->integer('activity_type_id'));
        }

        return (new Table($query, $request))
            ->withActionsColumn()
            ->select([
                'user_id', // is for the policy checks
                'completed_at', // see appends below
                'due_time', // for displaying in the due date column
                'end_time', // for displaying in the due date column
            ])
            ->appends([
                'is_completed', // for state change
                'is_due', // row class
            ])
            ->customizeable()
            ->rowBorderVariant(function (array $row) {
                if ($row['is_due']) {
                    return 'warning';
                } elseif ($row['is_completed']) {
                    return 'success';
                }

                return null;
            })
            // Policy
            ->with('guests')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Prepare global search query.
     */
    public function globalSearchQuery(ResourceRequest $request): Builder
    {
        return parent::globalSearchQuery($request)->select(['id', 'title', 'created_at']);
    }

    /**
     * Provides the resource available CRUD fields
     */
    public function fields(ResourceRequest $request): array
    {
        return [
            Text::make('title', __('activities::activity.title'))
                ->primary()
                ->withMeta(['attributes' => ['placeholder' => __('activities::activity.title')]])
                ->tapIndexColumn(fn (Column $column) => $column
                    ->width('300px')->minWidth('200px')
                    ->primary()
                    ->route(! $column->isForTrashedTable() ? '/activities/{id}/edit' : '')
                )
                ->rules(StringRule::make())
                ->creationRules('required')
                ->updateRules('filled')
                ->required(true),

            ActivityTypeField::make()
                ->primary()
                ->rules('filled')
                ->required(is_null(ActivityType::getDefaultType()))
                ->creationRules($isTypeRequiredRule = Rule::requiredIf(is_null(ActivityType::getDefaultType())))
                ->importRules($isTypeRequiredRule),

            ActivityDueDate::make(__('activities::activity.due_date'))
                ->tapIndexColumn(fn (Column $column) => $column->queryWhenHidden()) // for row class
                ->width('half')
                ->rules('required_with:due_time')
                ->creationRules('required')
                ->importRules('required')
                ->required(true)
                ->inlineEditWith($this->getDateFieldsForInlineEditing())
                ->updateRules(['required_with:end_date', 'required_with:end_time', 'filled']),

            ActivityEndDate::make(__('activities::activity.end_date'))
                ->tapIndexColumn(fn (Column $column) => $column->queryWhenHidden()) // for due date inlin edit
                ->rules(['required_with:end_time', 'filled'])
                ->updateRules(['required_with:due_date', 'required_with:due_time'])
                ->width('half')
                ->inlineEditWith($this->getDateFieldsForInlineEditing())
                ->hideFromIndex(),

            Reminder::make(
                'reminder_minutes_before',
                __('activities::activity.reminder').($request->isZapier() ? ' (minutes before due)' : '')
            )
                ->searchable(false)
                ->withDefaultValue(30)
                ->help($this->resource?->is_reminded ? __('activities::activity.reminder_update_info') : null)
                ->onlyOnForms()
                // Max is 40320 minutes, 4 weeks, as Google events max is 4 weeks
                ->rules(['not_in:0', 'max:40320'])
                ->provideSampleValueUsing(fn () => config('core.defaults.reminder_minutes'))
                ->cancelable(),

            User::make(__('activities::activity.owner'))
                ->primary()
                ->acceptLabelAsValue(false)
                ->withoutClearAction()
                ->creationRules('required')
                ->updateRules('filled')
                ->importRules('required')
                ->required(true)
                ->notification(\Modules\Activities\App\Notifications\UserAssignedToActivity::class)
                ->trackChangeDate('owner_assigned_date'),

            GuestsSelect::make('guests', __('activities::activity.guests'))
                ->onlyOnForms()
                ->excludeFromExport()
                ->excludeFromImport()
                ->rules(['nullable', 'array']),

            Editor::make('description', __('activities::activity.description'))
                ->rules(['nullable', 'string'])
                ->helpDisplay('text')
                ->help(__('activities::activity.description_info'))
                ->withMeta(['attributes' => ['placeholder' => __('activities::activity.description_info')]])
                ->minimal()
                ->toggleable()
                ->onlyOnForms(),

            DateTime::make('owner_assigned_date', __('activities::activity.owner_assigned_date'))
                ->onlyOnIndex()
                ->excludeFromImport()
                ->hidden(),

            Editor::make('note', __('activities::activity.note'))
                ->withMentions()
                ->minimal()
                ->help(__('activities::activity.note_info'))
                ->helpDisplay('text')
                ->withMeta(['attributes' => ['placeholder' => __('activities::activity.note_info')]])
                ->hideFromIndex()
                ->rules(['nullable', 'string']),

            BelongsTo::make('creator', UserModel::class, __('core::app.created_by'))
                ->excludeFromImport()
                ->onlyOnIndex()
                ->hidden(),

            Heading::make(__('core::resource.associate_with_records'))
                ->excludeFromUpdate(fn () => app(ResourceRequest::class)->viaResource())
                ->excludeFromCreate(fn () => app(ResourceRequest::class)->viaResource())
                ->titleIcon('Link'),

            Contacts::make()
                ->hideFromIndex()
                ->displayOptionViewActionFromPath()
                ->exceptOnForms(fn () => app(ResourceRequest::class)->viaResource()),

            Companies::make()
                ->hideFromIndex()
                ->displayOptionViewActionFromPath()
                ->exceptOnForms(fn () => app(ResourceRequest::class)->viaResource()),

            Deals::make()
                ->hideFromIndex()
                ->excludeFromIndex()
                ->displayOptionViewActionFromPath()
                ->exceptOnForms(fn () => app(ResourceRequest::class)->viaResource()),

            DateTime::make('reminded_at', __('activities::activity.reminder_sent_date'))
                ->onlyOnIndex()
                ->excludeFromImport()
                ->hidden(),

            Boolean::make('is_completed', __('activities::activity.is_completed'))
                ->onlyOnForms()
                ->rules(['nullable', 'boolean'])
                ->searchable(false)
                ->excludeFromImport()
                ->excludeFromExport()
                ->hidden()
                ->fillUsing(function ($model, $attribute, ResourceRequest $request, $value, $requestAttribute) {
                    if (is_null($value)) {
                        return $value;
                    }

                    $markAsCompleted = filter_var($value, FILTER_VALIDATE_BOOLEAN);

                    if (! $model->exists) {
                        $model->completed_at = $markAsCompleted ? now() : null;
                    } else {
                        $isCompleted = $model->isCompleted;

                        if (! $isCompleted && $markAsCompleted === true) {
                            $model->completed_at = now();
                        } elseif ($isCompleted && $markAsCompleted === false) {
                            $model->completed_at = null;
                        }
                    }
                }),

            DateTime::make('completed_at', __('activities::activity.completed_at'))
                ->tapIndexColumn(fn (Column $column) => $column->queryWhenHidden())
                ->onlyOnIndex()
                ->excludeFromImport()
                ->hidden(),

            DateTime::make('updated_at', __('core::app.updated_at'))
                ->excludeFromImportSample()
                ->onlyOnIndex()
                ->hidden(),

            DateTime::make('created_at', __('core::app.created_at'))
                ->excludeFromImportSample()
                ->onlyOnIndex(),
        ];
    }

    protected function getDateFieldsForInlineEditing(): array
    {
        return [
            ActivityDueDate::make(__('activities::activity.due_date'))
                ->rules('required_with:due_time')
                ->hideLabel(false),
            ActivityEndDate::make(__('activities::activity.end_date'))
                ->rules(['required_with:end_time', 'filled'])
                ->updateRules(['required_with:due_date', 'required_with:due_time'])
                ->hideLabel(false),
        ];
    }

    /**
     * Get the resource available filters
     */
    public function filters(ResourceRequest $request): array
    {
        return [
            TextFilter::make('title', __('activities::activity.title'))->withoutNullOperators(),
            UserFilter::make(__('activities::activity.owner'))->withoutNullOperators(),
            DateTimeFilter::make('owner_assigned_date', __('activities::activity.owner_assigned_date')),

            SelectFilter::make('activity_type_id', __('activities::activity.type.type'))
                ->valueKey('id')
                ->labelKey('name')
                ->options(function () {
                    return ActivityType::get(['id', 'name'])->map(fn (ActivityType $type) => [
                        'id' => $type->id,
                        'name' => $type->name,
                    ]);
                }),

            RadioFilter::make('is_completed', __('activities::activity.is_completed'))->options([
                true => __('core::app.yes'),
                false => __('core::app.no'),
            ])->query(function ($builder, $value, $condition) {
                $method = $value ? 'completed' : 'incomplete';

                return $builder->{$method}($condition);
            }),

            with(DateTimeFilter::make('due_date', __('activities::activity.due_date')), function ($filter) {
                return $filter->query($this->dueAndEndDateFilterQueryCallback($filter));
            }),

            with(DateTimeFilter::make('end_date', __('activities::activity.end_date')), function ($filter) {
                return $filter->query($this->dueAndEndDateFilterQueryCallback($filter));
            }),

            DateTimeFilter::make('reminder_at', __('activities::activity.reminder')),
            UserFilter::make(__('core::app.created_by'), 'created_by')->withoutNullOperators()->canSeeWhen('view all activities'),
            OverdueActivities::make(),
            OpenActivities::make(),
            DueTodayActivities::make(),
            DueThisWeekActivities::make(),
            DateTimeFilter::make('updated_at', __('core::app.updated_at')),
            DateTimeFilter::make('created_at', __('core::app.created_at')),
        ];
    }

    /**
     * Get the query for the due and end date filter query callback
     *
     * @return callable
     */
    protected function dueAndEndDateFilterQueryCallback($filter)
    {
        return function ($builder, $value, $condition, $sqlOperator, $rule, Parser $parser) use ($filter) {
            $rule->query->rule = static::$model::dueDateQueryExpression();

            return $parser->makeQueryWhenDate($builder, $filter, $rule, $sqlOperator['operator'], $value, $condition);
        };
    }

    /**
     * Provide the criteria that should be used to query only records that the logged-in user is authorized to view
     */
    public function viewAuthorizedRecordsCriteria(): string
    {
        return ViewAuthorizedActivitiesCriteria::class;
    }

    /**
     * Get the json resource that should be used for json response
     */
    public function jsonResource(): string
    {
        return ActivityResource::class;
    }

    /**
     * Provides the resource available actions
     */
    public function actions(ResourceRequest $request): array
    {
        return [
            (new \Modules\Activities\App\Actions\MarkActivityAsComplete)->onlyOnIndex(),
            (new \Modules\Activities\App\Actions\DownloadIcsFile)->onlyOnDetail(),

            new \Modules\Core\App\Actions\BulkEditAction($this),

            (new DeleteAction)->authorizedToRunWhen(function (ActionRequest $request, Model $model, int $total) {
                return $request->user()->can($total > 1 ? 'bulkDelete' : 'delete', $model);
            }),
        ];
    }

    /**
     * Get the resource available cards
     */
    public function cards(): array
    {
        return [
            (new \Modules\Activities\App\Cards\MyActivities)
                ->help(__('activities::activity.cards.my_activities_info'))
                // Only for refresh events, float is handled in "MyActivitiesCard.vue"
                ->floatResourceInEditMode(static::name()),
            (new \Modules\Activities\App\Cards\UpcomingUserActivities)
                ->help(__('activities::activity.cards.upcoming_info'))
                ->floatResourceInEditMode(static::name()),
            (new \Modules\Activities\App\Cards\ActivitiesCreatedBySaleAgent)
                ->canSeeWhen('view all activities')
                ->color('success')
                ->help(__('activities::activity.cards.created_by_agent_info')),
        ];
    }

    /**
     * Get the displayable singular label of the resource
     */
    public static function singularLabel(): string
    {
        return __('activities::activity.activity');
    }

    /**
     * Get the displayable label of the resource
     */
    public static function label(): string
    {
        return __('activities::activity.activities');
    }

    /**
     * Get the resource relationship name when it's associated
     */
    public function associateableName(): string
    {
        return 'activities';
    }

    /**
     * Create the query when the resource is associated and the data is intended for the timeline.
     */
    public function timelineQuery(Model $subject, ResourceRequest $request): Builder
    {
        return parent::timelineQuery($subject, $request)
            ->with('guests.guestable')
            ->reorder()
            // Pinned are always first, then the non-completed sorted by due date asc
            ->orderBy((new PinnedTimelineSubject)->getQualifiedCreatedAtColumn(), 'desc')
            ->orderBy('completed_at', 'asc')
            ->orderBy(static::$model::dueDateQueryExpression(), 'asc')
            ->criteria($this->viewAuthorizedRecordsCriteria());
    }

    /**
     * Register permissions for the resource
     */
    public function registerPermissions(): void
    {
        $this->registerCommonPermissions();

        Permissions::register(function ($manager) {
            $manager->group($this->name(), function ($manager) {
                $manager->view('view', [
                    'permissions' => [
                        'view attends and owned activities' => __('activities::activity.permissions.attends_and_owned'),
                    ],
                ]);

                $manager->view('export', [
                    'permissions' => [
                        'export activities' => __('core::app.export.export'),
                    ],
                ]);
            });
        });
    }

    /**
     * Register the settings menu items for the resource
     */
    public function settingsMenu(): array
    {
        return [
            SettingsMenuItem::make(__('activities::activity.activities'), '/settings/activities', 'Calendar')->order(21),
        ];
    }
}
