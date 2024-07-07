<?php
 

namespace Modules\Documents\App\Resources;

use Illuminate\Database\Eloquent\Builder;
use Modules\Billable\App\Contracts\BillableResource;
use Modules\Billable\App\Fields\Amount;
use Modules\Billable\App\Filters\BillableProductsFilter;
use Modules\Contacts\App\Fields\Companies;
use Modules\Contacts\App\Fields\Contacts;
use Modules\Core\App\Actions\DeleteAction;
use Modules\Core\App\Contracts\Resources\Cloneable;
use Modules\Core\App\Contracts\Resources\HasOperations;
use Modules\Core\App\Contracts\Resources\Tableable;
use Modules\Core\App\Fields\BelongsTo;
use Modules\Core\App\Fields\DateTime;
use Modules\Core\App\Fields\FieldsCollection;
use Modules\Core\App\Fields\Text;
use Modules\Core\App\Fields\User;
use Modules\Core\App\Filters\DateTime as DateTimeFilter;
use Modules\Core\App\Filters\Numeric as NumericFilter;
use Modules\Core\App\Filters\Text as TextFilter;
use Modules\Core\App\Http\Requests\ActionRequest;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Menu\MenuItem;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Resource\Events\ResourceRecordCreated;
use Modules\Core\App\Resource\Events\ResourceRecordUpdated;
use Modules\Core\App\Resource\Resource;
use Modules\Core\App\Settings\SettingsMenuItem;
use Modules\Core\App\Table\BelongsToColumn;
use Modules\Core\App\Table\Column;
use Modules\Core\App\Table\Table;
use Modules\Deals\App\Fields\Deals;
use Modules\Documents\App\Concerns\ValidatesDocument;
use Modules\Documents\App\Criteria\ViewAuthorizedDocumentsCriteria;
use Modules\Documents\App\Enums\DocumentStatus;
use Modules\Documents\App\Filters\DocumentBrandFilter;
use Modules\Documents\App\Filters\DocumentStatusFilter;
use Modules\Documents\App\Filters\DocumentTypeFilter;
use Modules\Documents\App\Http\Resources\DocumentResource;
use Modules\Documents\App\Models\DocumentType;
use Modules\Documents\App\Services\DocumentService;
use Modules\Users\App\Filters\ResourceUserTeamFilter;
use Modules\Users\App\Filters\UserFilter;

class Document extends Resource implements BillableResource, Cloneable, HasOperations, Tableable
{
    use ValidatesDocument;

    /**
     * The column the records should be default ordered by when retrieving
     */
    public static string $orderBy = 'title';

    /**
     * Indicates whether the resource is globally searchable
     */
    public static bool $globallySearchable = true;

    /**
     * The resource displayable icon.
     */
    public static ?string $icon = 'DocumentText';

    /**
     * The model the resource is related to
     */
    public static string $model = 'Modules\Documents\App\Models\Document';

    /**
     * Get the menu items for the resource
     */
    public function menu(): array
    {
        return [
            MenuItem::make(static::label(), '/documents', static::$icon)
                ->position(20)
                ->inQuickCreate()
                ->keyboardShortcutChar('F'),
        ];
    }

    /**
     * Create new resource record in storage.
     */
    public function create(Model $model, ResourceRequest $request): Model
    {
        $document = (new DocumentService)->create($model, $request->all());

        ResourceRecordCreated::dispatch($document, $this);

        return $document;
    }

    /**
     * Update resource record in storage.
     */
    public function update(Model $model, ResourceRequest $request): Model
    {
        $document = (new DocumentService)->update($model, $request->all());

        ResourceRecordUpdated::dispatch($document, $this);

        return $document;
    }

    /**
     * Get the resource relationship name when it's associated
     */
    public function associateableName(): string
    {
        return 'documents';
    }

    /**
     * Get the resource available cards
     */
    public function cards(): array
    {
        return [
            (new \Modules\Documents\App\Cards\SentDocumentsByDay)->withUserSelection(function ($instance) {
                return $instance->authorizedToFilterByUser();
            })->color('success'),
            (new \Modules\Documents\App\Cards\DocumentsByType)->onlyOnDashboard()->help(__('core::app.cards.creation_date_info')),
            (new \Modules\Documents\App\Cards\DocumentsByStatus)->refreshOnActionExecuted()->help(__('core::app.cards.creation_date_info')),
        ];
    }

    /**
     * Provide the resource table class instance.
     */
    public function table(Builder $query, ResourceRequest $request): Table
    {
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return (new Table($query, $request))
            ->customizeable()
            ->withActionsColumn()
            ->appends(['public_url'])
            ->orderBy('created_at', 'desc')
            ->select([
                'uuid', // for public_url append
                'user_id', // user_id is for the policy checks
                'status', // for showing the dropdown send document item and disable inline edit checks
            ]);
    }

    /**
     * Get the json resource that should be used for json response
     */
    public function jsonResource(): string
    {
        return DocumentResource::class;
    }

    /**
     * Prepare global search query.
     */
    public function globalSearchQuery(ResourceRequest $request): Builder
    {
        return parent::globalSearchQuery($request)->select(['id', 'title', 'created_at']);
    }

    /**
     * Get columns that should be used for global search.
     */
    public function globalSearchColumns(): array
    {
        return ['title' => 'like'];
    }

    /**
     * Get the resource search columns.
     */
    public function searchableColumns(): array
    {
        return [
            'title' => 'like',
            'status',
            'amount',
            'brand_id',
            'document_type_id',
            'sent_by',
            'user_id',
            'created_by',
        ];
    }

    /**
     * Provide the criteria that should be used to query only records that the logged-in user is authorized to view
     */
    public function viewAuthorizedRecordsCriteria(): string
    {
        return ViewAuthorizedDocumentsCriteria::class;
    }

    /**
     * Clone the resource record from the given id
     */
    public function clone(Model $model, int $userId): Model
    {
        return $model->clone($userId);
    }

    /**
     * Resolve the fields for placeholders.
     */
    public function fieldsForPlaceholders(): FieldsCollection
    {
        return $this->fieldsForIndex()->filterForPlaceholders();
    }

    /**
     * Resolve the fields for index.
     */
    public function fieldsForIndex(): FieldsCollection
    {
        return new FieldsCollection([
            Text::make('title', __('documents::fields.documents.title'))
                ->required()
                ->tapIndexColumn(fn (Column $column) => $column
                    ->width('300px')->minWidth('200px')
                    ->primary()
                    ->route(! $column->isForTrashedTable() ? ['name' => 'view-document', 'params' => ['id' => '{id}']] : '')
                )
                ->disableInlineEdit(fn ($model) => $model->status === DocumentStatus::ACCEPTED),

            BelongsTo::make('type', DocumentType::class, __('documents::document.type.type'))
                ->required()
                ->displayAsBadges()
                ->tapIndexColumn(fn (BelongsToColumn $column) => $column
                    ->width('200px')
                    ->select('swatch_color')
                    ->appends(['swatch_color', 'icon'])
                ),

            // TODO: Not displayed properly on trashed table.
            Text::make('status', __('documents::document.status.status'))
                ->tapIndexColumn(fn (Column $column) => $column
                    ->width('200px')
                    ->centered()
                )
                ->displayUsing(fn ($model, $value) => DocumentStatus::tryFrom($value)->displayName()) // for mail placeholder
                ->resolveUsing(fn ($model) => $model->status->value)
                ->disableInlineEdit(),

            User::make(__('documents::fields.documents.user.name'))
                ->required()
                ->tapIndexColumn(fn (Column $column) => $column->queryWhenHidden()) // policy
                ->disableInlineEdit(fn ($model) => $model->status === DocumentStatus::ACCEPTED),

            Amount::make('amount', __('documents::fields.documents.amount'))
                ->currency()
                ->onlyProducts()
                ->disableInlineEdit(fn ($model) => $model->status === DocumentStatus::ACCEPTED),

            Contacts::make()->hidden(),

            Companies::make()->hidden(),

            Deals::make()->hidden(),

            User::make(__('core::app.created_by'), 'creator', 'created_by')
                ->disableInlineEdit()
                ->hidden(),

            DateTime::make('last_date_sent', __('documents::fields.documents.last_date_sent'))
                ->disableInlineEdit()
                ->hidden(),

            DateTime::make('original_date_sent', __('documents::fields.documents.original_date_sent'))
                ->disableInlineEdit()
                ->hidden(),

            DateTime::make('accepted_at', __('documents::fields.documents.accepted_at'))
                ->disableInlineEdit()
                ->hidden(),

            DateTime::make('owner_assigned_date', __('documents::fields.documents.owner_assigned_date'))
                ->disableInlineEdit()
                ->hidden(),

            DateTime::make('updated_at', __('core::app.updated_at'))
                ->disableInlineEdit()
                ->hidden(),

            DateTime::make('created_at', __('core::app.created_at'))
                ->disableInlineEdit()
                ->hidden(),
        ]);
    }

    /**
     * Get the resource available Filters
     */
    public function filters(ResourceRequest $request): array
    {
        return [
            TextFilter::make('title', __('documents::fields.documents.title'))->withoutNullOperators(),
            DocumentTypeFilter::make(),
            NumericFilter::make('amount', __('documents::fields.documents.amount')),
            DocumentBrandFilter::make(),
            DocumentStatusFilter::make(),
            DateTimeFilter::make('accepted_at', __('documents::fields.documents.accepted_at')),
            UserFilter::make(__('documents::fields.documents.user.name'))->withoutNullOperators(),
            ResourceUserTeamFilter::make(__('users::team.owner_team')),
            DateTimeFilter::make('owner_assigned_date', __('documents::fields.documents.owner_assigned_date')),
            BillableProductsFilter::make(),
            DateTimeFilter::make('last_date_sent', __('documents::fields.documents.last_date_sent')),
            DateTimeFilter::make('original_date_sent', __('documents::fields.documents.original_date_sent')),
            UserFilter::make(__('documents::document.sent_by'), 'sent_by')->canSeeWhen('view all documents'),
            UserFilter::make(__('core::app.created_by'), 'created_by')->withoutNullOperators()->canSeeWhen('view all documents'),
            DateTimeFilter::make('updated_at', __('core::app.updated_at')),
            DateTimeFilter::make('created_at', __('core::app.created_at')),
        ];
    }

    /**
     * Create the query when the resource is associated and the data is intended for the timeline.
     */
    public function timelineQuery(Model $subject, ResourceRequest $request): Builder
    {
        return parent::timelineQuery($subject, $request)->criteria($this->viewAuthorizedRecordsCriteria());
    }

    /**
     * Provides the resource available actions
     */
    public function actions(ResourceRequest $request): array
    {
        return [
            new \Modules\Users\App\Actions\AssignOwnerAction,

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
        return __('documents::document.documents');
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return __('documents::document.document');
    }

    /**
     * Register permissions for the resource
     */
    public function registerPermissions(): void
    {
        $this->registerCommonPermissions();
    }

    /**
     * Register the settings menu items for the resource
     */
    public function settingsMenu(): array
    {
        return [
            SettingsMenuItem::make(__('documents::document.documents'), '/settings/documents', 'DocumentText')->order(23),
        ];
    }
}
