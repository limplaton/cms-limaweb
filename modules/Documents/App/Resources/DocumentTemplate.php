<?php
 

namespace Modules\Documents\App\Resources;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Modules\Core\App\Contracts\Resources\Cloneable;
use Modules\Core\App\Contracts\Resources\HasOperations;
use Modules\Core\App\Contracts\Resources\Tableable;
use Modules\Core\App\Fields\Boolean;
use Modules\Core\App\Fields\DateTime;
use Modules\Core\App\Fields\FieldsCollection;
use Modules\Core\App\Fields\ID;
use Modules\Core\App\Fields\Text;
use Modules\Core\App\Fields\User as UserField;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Resource\Resource;
use Modules\Core\App\Rules\StringRule;
use Modules\Core\App\Rules\UniqueResourceRule;
use Modules\Core\App\Table\Column;
use Modules\Core\App\Table\Table;
use Modules\Documents\App\Criteria\TemplatesForUserCriteria;
use Modules\Documents\App\Enums\DocumentViewType;
use Modules\Documents\App\Http\Resources\DocumentTemplateResource;
use Modules\Documents\App\Models\DocumentTemplate as DocumentTemplateModel;

class DocumentTemplate extends Resource implements Cloneable, HasOperations, Tableable
{
    /**
     * The column the records should be default ordered by when retrieving
     */
    public static string $orderBy = 'name';

    /**
     * The model the resource is related to
     */
    public static string $model = 'Modules\Documents\App\Models\DocumentTemplate';

    /**
     * Provide the criteria that should be used to query only records that the logged-in user is authorized to view
     */
    public function viewAuthorizedRecordsCriteria(): string
    {
        return TemplatesForUserCriteria::class;
    }

    /**
     * Get the json resource that should be used for json response
     */
    public function jsonResource(): string
    {
        return DocumentTemplateResource::class;
    }

    /**
     * Clone the resource record from the given id
     */
    public function clone(Model $model, int $userId): Model
    {
        return $model->clone($userId);
    }

    /**
     * Provide the resource table class instance.
     */
    public function table(Builder $query, ResourceRequest $request): Table
    {
        return (new Table($query, $request))->withActionsColumn();
    }

    /**
     * Create new resource template in storage.
     */
    public function create(Model $model, ResourceRequest $request): Model
    {
        $this->performCreate($model->fill($request->all()), $request);

        return $model;
    }

    /**
     * Create new resource template in storage.
     */
    public function update(Model $model, ResourceRequest $request): Model
    {
        $this->performUpdate($model->fill($request->all()), $request);

        return $model;
    }

    /**
     * Get the resource search columns.
     */
    public function searchableColumns(): array
    {
        return ['name' => 'like'];
    }

    /**
     * Get the fields for index.
     */
    public function fieldsForIndex(): FieldsCollection
    {
        return (new FieldsCollection([
            ID::make(),

            Text::make('name', __('documents::document.template.name'))
                ->tapIndexColumn(
                    fn (Column $column) => $column->route([
                        'name' => 'edit-document-template', 'params' => ['id' => '{id}'],
                    ])
                ),

            Boolean::make('is_shared', __('documents::document.template.is_shared')),

            UserField::make(__('core::app.created_by')),

            DateTime::make('created_at', __('core::app.created_at')),

            DateTime::make('updated_at', __('core::app.updated_at')),
        ]))->disableInlineEdit();
    }

    /**
     * Set the resource rules available for create and update
     */
    public function rules(ResourceRequest $request): array
    {
        return [
            'name' => [
                'required',
                StringRule::make(),
                UniqueResourceRule::make(DocumentTemplateModel::class),
            ],
            'content' => ['required', 'string'],
            'is_shared' => ['nullable', 'boolean'],
            'view_type' => ['nullable', Rule::enum(DocumentViewType::class)],
        ];
    }

    /**
     * Get the displayable singular label of the resource
     */
    public static function singularLabel(): string
    {
        return __('documents::document.template.template');
    }

    /**
     * Get the displayable label of the resource
     */
    public static function label(): string
    {
        return __('documents::document.template.templates');
    }
}
