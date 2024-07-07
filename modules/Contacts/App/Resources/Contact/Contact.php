<?php
 

namespace Modules\Contacts\App\Resources\Contact;

use App\Http\View\FrontendComposers\Template;
use Illuminate\Database\Eloquent\Builder;
use Modules\Activities\App\Fields\NextActivityDate;
use Modules\Activities\App\Filters\ResourceActivitiesFilter;
use Modules\Comments\App\Contracts\PipesComments;
use Modules\Contacts\App\Cards\ContactsByDay;
use Modules\Contacts\App\Cards\ContactsBySource;
use Modules\Contacts\App\Cards\RecentlyCreatedContacts;
use Modules\Contacts\App\Criteria\ViewAuthorizedContactsCriteria;
use Modules\Contacts\App\Fields\Companies;
use Modules\Contacts\App\Fields\Phone;
use Modules\Contacts\App\Fields\Source;
use Modules\Contacts\App\Filters\AddressOperandFilter;
use Modules\Contacts\App\Filters\SourceFilter;
use Modules\Contacts\App\Http\Resources\ContactResource;
use Modules\Contacts\App\Models\Contact as ContactModel;
use Modules\Contacts\App\Models\Phone as PhoneModel;
use Modules\Contacts\App\Resources\Contact\Pages\DetailComponent;
use Modules\Core\App\Actions\DeleteAction;
use Modules\Core\App\Contracts\Resources\AcceptsCustomFields;
use Modules\Core\App\Contracts\Resources\AcceptsUniqueCustomFields;
use Modules\Core\App\Contracts\Resources\Exportable;
use Modules\Core\App\Contracts\Resources\HasEmail;
use Modules\Core\App\Contracts\Resources\HasOperations;
use Modules\Core\App\Contracts\Resources\Importable;
use Modules\Core\App\Contracts\Resources\Mediable;
use Modules\Core\App\Contracts\Resources\Tableable;
use Modules\Core\App\Facades\ChangeLogger;
use Modules\Core\App\Facades\Fields;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Facades\Permissions;
use Modules\Core\App\Fields\Country;
use Modules\Core\App\Fields\DateTime;
use Modules\Core\App\Fields\Email;
use Modules\Core\App\Fields\ID;
use Modules\Core\App\Fields\RelationshipCount;
use Modules\Core\App\Fields\Tags;
use Modules\Core\App\Fields\Text;
use Modules\Core\App\Fields\User;
use Modules\Core\App\Filters\DateTime as DateTimeFilter;
use Modules\Core\App\Filters\Filter;
use Modules\Core\App\Filters\HasFilter;
use Modules\Core\App\Filters\Operand;
use Modules\Core\App\Filters\OperandFilter;
use Modules\Core\App\Filters\Tags as TagsFilter;
use Modules\Core\App\Filters\Text as TextFilter;
use Modules\Core\App\Http\Requests\ActionRequest;
use Modules\Core\App\Http\Requests\ImportRequest;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Menu\MenuItem;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Resource\Import\Import;
use Modules\Core\App\Resource\Resource;
use Modules\Core\App\Rules\StringRule;
use Modules\Core\App\Support\CountryCallingCode;
use Modules\Core\App\Table\Column;
use Modules\Core\App\Table\Table;
use Modules\Deals\App\Fields\Deals;
use Modules\Deals\App\Filters\ResourceDealsFilter;
use Modules\Documents\App\Filters\ResourceDocumentsFilter;
use Modules\MailClient\App\Filters\ResourceEmailsFilter;
use Modules\Notes\App\Fields\ImportNote;
use Modules\Users\App\Filters\ResourceUserTeamFilter;
use Modules\Users\App\Filters\UserFilter;

class Contact extends Resource implements AcceptsCustomFields, AcceptsUniqueCustomFields, Exportable, HasEmail, HasOperations, Importable, Mediable, PipesComments, Tableable
{
    /**
     * Indicates whether the resource has Zapier hooks
     */
    public static bool $hasZapierHooks = true;

    /**
     * The column the records should be default ordered by when retrieving
     */
    public static string $orderBy = 'first_name';

    /**
     * Indicates whether the resource has detail view.
     */
    public static bool $hasDetailView = true;

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
    public static ?string $icon = 'Users';

    /**
     * Indicates whether the resource fields are customizeable
     */
    public static bool $fieldsCustomizable = true;

    /**
     * The model the resource is related to
     */
    public static string $model = 'Modules\Contacts\App\Models\Contact';

    /**
     * Get the resource model email address field name
     */
    public function emailAddressField(): string
    {
        return 'email';
    }

    /**
     * Get the menu items for the resource
     */
    public function menu(): array
    {
        return [
            MenuItem::make(static::label(), '/contacts', static::$icon)
                ->position(25)
                ->inQuickCreate()
                ->keyboardShortcutChar('C'),
        ];
    }

    /**
     * Get the resource relationship name when it's associated
     */
    public function associateableName(): string
    {
        return 'contacts';
    }

    /**
     * Get the resource available cards
     */
    public function cards(): array
    {
        return [
            (new ContactsByDay)->refreshOnActionExecuted()->help(__('core::app.cards.creation_date_info')),
            (new ContactsBySource)->refreshOnActionExecuted()->help(__('core::app.cards.creation_date_info'))->color('info'),
            (new RecentlyCreatedContacts)->onlyOnDashboard()->floatResourceInDetailMode(static::name()),
        ];
    }

    /**
     * Prepare display query.
     */
    public function displayQuery(): Builder
    {
        return parent::displayQuery()->with([
            'media',
            'companies.phones', // phones are for calling
            'deals.stage', // stage is for card display on detail
        ]);
    }

    /**
     * Prepare global search query.
     */
    public function globalSearchQuery(ResourceRequest $request): Builder
    {
        return parent::globalSearchQuery($request)->select(
            ['id', 'email', 'first_name', 'last_name', 'created_at']
        );
    }

    /**
     * Get the resource search columns.
     */
    public function searchableColumns(): array
    {
        return array_merge(
            parent::searchableColumns(),
            ['full_name' => [
                'column' => $this->newModel()->nameQueryExpression(),
                'condition' => 'like',
            ]],
        );
    }

    /**
     * Get columns that should be used for global search.
     */
    public function globalSearchColumns(): array
    {
        return array_merge(
            parent::globalSearchColumns(),
            ['full_name' => [
                'column' => $this->newModel()->nameQueryExpression(),
                'condition' => 'like',
            ]],
        );
    }

    /**
     * Provide the resource table class instance.
     */
    public function table(Builder $query, ResourceRequest $request): Table
    {
        return new ContactTable($query, $request);
    }

    /**
     * Get the json resource that should be used for json response
     */
    public function jsonResource(): string
    {
        return ContactResource::class;
    }

    /**
     * Provide the criteria that should be used to query only records that the logged-in user is authorized to view
     */
    public function viewAuthorizedRecordsCriteria(): string
    {
        return ViewAuthorizedContactsCriteria::class;
    }

    /**
     * Provides the resource available CRUD fields
     */
    public function fields(ResourceRequest $request): array
    {
        return [
            ID::make()->hidden(),

            $firstName = Text::make('first_name', __('contacts::fields.contacts.first_name'))
                ->primary()
                ->rules(StringRule::make())
                ->creationRules('required')
                ->updateRules('filled')
                ->importRules('required')
                ->hideFromDetail()
                ->excludeFromSettings(Fields::DETAIL_VIEW)
                ->excludeFromIndex()
                ->required(true),

            $lastName = Text::make('last_name', __('contacts::fields.contacts.last_name'))
                ->rules(['nullable', StringRule::make()])
                ->hideFromDetail()
                ->excludeFromSettings(Fields::DETAIL_VIEW)
                ->excludeFromIndex(),

            Text::make('contact', __('contacts::contact.contact'))
                ->searchable(false)
                ->excludeFromExport()
                ->excludeFromImport()
                ->excludeFromZapierResponse()
                ->onlyOnIndex()
                ->inlineEditWith([$firstName, $lastName])
                ->tapIndexColumn(fn (Column $column) => $column->width('300px')->minWidth('200px')
                    ->primary()
                    ->select($cols = ['first_name', 'last_name']) // for inline editing
                    ->appends($cols)
                    ->route(! $column->isForTrashedTable() ? ['name' => 'view-contact', 'params' => ['id' => '{id}']] : '')
                    ->queryAs(ContactModel::nameQueryExpression('contact'))
                    ->orderByUsing(function (Builder $query, string $direction) {
                        return $query->orderBy(ContactModel::nameQueryExpression(), $direction);
                    })
                ),

            Email::make('email', __('contacts::fields.contacts.email'))
                ->rules(['nullable', StringRule::make(), 'email'])
                ->unique(ContactModel::class)
                ->unique(\Modules\Users\App\Models\User::class)
                ->validationMessages(['unique' => __('contacts::contact.validation.email.unique')])
                ->showValueWhenUnauthorizedToView(),

            Phone::make('phones', __('contacts::fields.contacts.phone'))
                ->checkPossibleDuplicatesWith(
                    '/contacts/search', ['search_fields' => 'phones.number'], 'contacts::contact.possible_duplicate'
                )
                ->canUnmarkUnique()
                ->unique(ContactModel::class, __('contacts::contact.validation.phone.unique'))
                ->requireCallingPrefix(
                    function (ResourceRequest|ImportRequest $request) {
                        if ((bool) settings('require_calling_prefix_on_phones')) {
                            return $this->resource?->country_id ?? $request->country_id ?? CountryCallingCode::guess() ?? true;
                        }
                    }
                ),

            Tags::make()
                ->forType(ContactModel::TAGS_TYPE)
                ->rules(['sometimes', 'nullable', 'array'])
                ->hideFromDetail()
                ->hideFromIndex()
                ->excludeFromSettings(Fields::DETAIL_VIEW),

            User::make(__('contacts::fields.contacts.user.name'))
                ->primary()
                ->acceptLabelAsValue(false)
                ->withMeta(['attributes' => ['placeholder' => __('core::app.no_owner')]])
                ->notification(\Modules\Contacts\App\Notifications\UserAssignedToContact::class)
                ->trackChangeDate('owner_assigned_date')
                ->hideFromDetail()
                ->tapIndexColumn(fn (Column $column) => $column->queryWhenHidden()) // policy checks
                ->excludeFromSettings(Fields::DETAIL_VIEW)
                ->showValueWhenUnauthorizedToView(),

            Source::make(),

            Companies::make()
                ->excludeFromSettings(Fields::DETAIL_VIEW)
                ->hideFromDetail()
                ->hideFromIndex()
                ->order(1001),

            Deals::make()
                ->excludeFromSettings(Fields::DETAIL_VIEW)
                ->hideFromDetail()
                ->hideFromIndex()
                ->order(1002),

            DateTime::make('owner_assigned_date', __('contacts::fields.contacts.owner_assigned_date'))
                ->onlyOnIndex()
                ->excludeFromImport()
                ->hidden(),

            RelationshipCount::make('companies', __('contacts::company.total'))
                ->hidden(),

            RelationshipCount::make('deals', __('deals::deal.total'))
                ->hidden(),

            RelationshipCount::make('openDeals', __('deals::deal.open_deals'))
                ->hidden()
                ->excludeFromZapierResponse(),

            RelationshipCount::make('closedDeals', __('deals::deal.closed_deals'))
                ->hidden()
                ->excludeFromZapierResponse(),

            RelationshipCount::make('wonDeals', __('deals::deal.won_deals'))
                ->hidden()
                ->excludeFromZapierResponse(),

            RelationshipCount::make('lostDeals', __('deals::deal.lost_deals'))
                ->hidden()
                ->excludeFromZapierResponse(),

            RelationshipCount::make('unreadEmailsForUser', __('mailclient::inbox.unread_count'))
                ->hidden()
                ->authRequired()
                ->excludeFromZapierResponse(),

            RelationshipCount::make('incompleteActivitiesForUser', __('activities::activity.incomplete_activities'))
                ->hidden()
                ->authRequired()
                ->excludeFromZapierResponse(),

            RelationshipCount::make('documents', __('documents::document.total_documents'))
                ->hidden()
                ->excludeFromZapierResponse(),

            RelationshipCount::make('draftDocuments', __('documents::document.total_draft_documents'))
                ->hidden()
                ->excludeFromZapierResponse(),

            RelationshipCount::make('calls', __('calls::call.total_calls'))
                ->hidden(),

            Text::make('job_title', __('contacts::fields.contacts.job_title'))
                ->rules(['nullable', StringRule::make()])
                ->collapsed()
                ->hideFromIndex()
                ->hideWhenCreating(),

            Text::make('street', __('contacts::fields.contacts.street'))
                ->rules(['nullable', StringRule::make()])
                ->collapsed()
                ->hideFromIndex()
                ->hideWhenCreating(),

            Text::make('city', __('contacts::fields.contacts.city'))
                ->rules(['nullable', StringRule::make()])
                ->collapsed()
                ->hideFromIndex()
                ->hideWhenCreating(),

            Text::make('state', __('contacts::fields.contacts.state'))
                ->rules(['nullable', StringRule::make()])
                ->collapsed()
                ->hideFromIndex()
                ->hideWhenCreating(),

            Text::make('postal_code', __('contacts::fields.contacts.postal_code'))
                ->rules(['nullable', StringRule::make()])
                ->collapsed()
                ->hideFromIndex()
                ->hideWhenCreating(),

            Country::make(__('contacts::fields.contacts.country.name'))
                ->collapsed()
                ->hideFromIndex()
                ->hideWhenCreating(),

            NextActivityDate::make(),

            ImportNote::make(),

            DateTime::make('updated_at', __('core::app.updated_at'))
                ->excludeFromImportSample()
                ->onlyOnIndex()
                ->hidden(),

            DateTime::make('created_at', __('core::app.created_at'))
                ->excludeFromImportSample()
                ->onlyOnIndex()
                ->hidden(),
        ];
    }

    /**
     * Handle the "afterCreate" resource record hook.
     */
    public function afterCreate(Model $model, ResourceRequest $request): void
    {
        if ($model->email && (bool) settings('auto_associate_company_to_contact')) {
            $emailDomain = substr($model->email, strpos($model->email, '@') + 1);

            $request->findResource('companies')
                ->newQuery()
                ->where('domain', $emailDomain)->get()
                ->whenNotEmpty(function ($companies) use ($model) {
                    ChangeLogger::asSystem(fn () => $model->companies()->syncWithoutDetaching($companies->modelKeys()));
                });
        }
    }

    /**
     * Get the resource importable class
     */
    public function importable(): Import
    {
        return parent::importable()->lookupForDuplicatesUsing(function ($request) {
            if ($request->filled('email')) {
                if ($contact = $this->findByEmail($request->email, $this->newQueryWithTrashed())) {
                    return $contact;
                }
            }

            if ($request->filled('phones')) {
                return $this->findByPhones($request->phones);
            }
        });
    }

    /**
     * Find contact by email for the given query.
     */
    public function findByEmail(string $email, Builder $query): ?ContactModel
    {
        return $query->where('email', $email)->first();
    }

    /**
     * Find contact by phone numbers.
     */
    public function findByPhones(array $phones): ?ContactModel
    {
        $numbers = ! isset($phones[0]['number']) ? $phones : array_filter((array) data_get($phones, '*.number'));

        return PhoneModel::where('phoneable_type', ContactModel::class)
            ->whereIn('number', $numbers)
            ->first()?->phoneable;
    }

    /**
     * Get the resource available Filters
     */
    public function filters(ResourceRequest $request): array
    {
        return [
            TextFilter::make('first_name', __('contacts::fields.contacts.first_name'))->withoutNullOperators(),
            TextFilter::make('last_name', __('contacts::fields.contacts.last_name')),
            TextFilter::make('email', __('contacts::fields.contacts.email')),
            UserFilter::make(__('contacts::fields.contacts.user.name')),
            ResourceUserTeamFilter::make(__('users::team.owner_team')),
            DateTimeFilter::make('owner_assigned_date', __('contacts::fields.contacts.owner_assigned_date')),
            TagsFilter::make('tags', __('core::tags.tags'))->forType(ContactModel::TAGS_TYPE),
            ResourceDocumentsFilter::make(),
            ResourceActivitiesFilter::make(),
            SourceFilter::make(),
            TextFilter::make('job_title', __('contacts::fields.contacts.job_title')),
            AddressOperandFilter::make('contacts'),
            UserFilter::make(__('core::app.created_by'), 'created_by')->withoutNullOperators()->canSeeWhen('view all contacts'),

            HasFilter::make('phones', __('contacts::fields.contacts.phone'))->setOperands([
                Operand::from(TextFilter::make('number', __('contacts::fields.contacts.phone'))),
            ])->hideOperands(),

            ResourceDealsFilter::make(__('contacts::contact.contact')),
            ResourceEmailsFilter::make(),
            DateTimeFilter::make('next_activity_date', __('activities::activity.next_activity_date')),
            DateTimeFilter::make('updated_at', __('core::app.updated_at')),
            DateTimeFilter::make('created_at', __('core::app.created_at')),

            HasFilter::make('companies', __('contacts::company.company'))->setOperands(
                fn () => Innoclapps::resourceByName('companies')
                    ->resolveFilters($request)
                    ->reject(fn (Filter $filter) => $filter instanceof OperandFilter)
                    ->map(fn (Filter $filter) => Operand::from($filter))
                    ->values()
                    ->all()
            ),
        ];
    }

    /**
     * Provides the resource available actions
     */
    public function actions(ResourceRequest $request): array
    {
        return [
            new \Modules\Core\App\Actions\SearchInGoogleAction,

            new \Modules\Core\App\Actions\BulkEditAction($this),

            (new DeleteAction)->authorizedToRunWhen(function (ActionRequest $request, Model $model, int $total) {
                return $request->user()->can($total > 1 ? 'bulkDelete' : 'delete', $model);
            }),
        ];
    }

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return __('contacts::contact.contacts');
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return __('contacts::contact.contact');
    }

    /**
     * Register permissions for the resource
     */
    public function registerPermissions(): void
    {
        $this->registerCommonPermissions();

        Permissions::register(function ($manager) {
            $manager->group($this->name(), function ($manager) {
                $manager->view('export', [
                    'permissions' => [
                        'export contacts' => __('core::app.export.export'),
                    ],
                ]);
            });
        });
    }

    /**
     * Get the resource frontend template
     */
    public function frontendTemplate(): Template
    {
        return (new Template)->detailComponent(new DetailComponent);
    }

    /**
     * Serialize the resource
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'pages' => $this->frontendTemplate(),
        ]);
    }
}
