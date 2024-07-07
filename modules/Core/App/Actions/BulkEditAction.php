<?php
 

namespace Modules\Core\App\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\App\Fields\Field;
use Modules\Core\App\Http\Requests\ActionRequest;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Http\Requests\UpdateResourceRequest;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Resource\Resource;

class BulkEditAction extends Action
{
    /**
     * Indicates that the action will be hidden on the detail view.
     */
    public bool $hideOnDetail = true;

    /**
     * The action modal size. (sm, md, lg, xl, xxl)
     */
    public string $size = 'md';

    /**
     * Initialize new BulkEditAction instance.
     */
    public function __construct(protected Resource $resource)
    {
    }

    /**
     * Handle method.
     */
    public function handle(Collection $models, ActionFields $fields): void
    {
        DB::transaction(function () use ($models, $fields) {
            foreach ($models as $model) {
                $request = $this->createUpdateRequest($model, $fields->all());

                $this->resource->update($request->hydrateModel($model), $request);
            }
        });
    }

    /**
     * Create update request for the action.
     */
    protected function createUpdateRequest(Model $model, array $data): UpdateResourceRequest
    {
        return app(UpdateResourceRequest::class)
            ->setRecord($model)
            ->replace($data)
            ->setResource($this->resource->name())
            ->setResourceId($model->getKey());
    }

    /**
     * @param  \Illumindate\Database\Eloquent\Model  $model
     */
    public function authorizedToRun(ActionRequest $request, $model): bool
    {
        return $request->user()->can('update', $model);
    }

    /**
     * Get the action fields.
     */
    public function fields(ResourceRequest $request): array
    {
        return $this->resource->visibleFieldsForUpdate()->each(function (Field $field) {
            $field->prepareForBulkEdit();
        })->reject(
            fn (Field $field) => $field->isUnique() || $field->excludeFromBulkEdit
        )->all();
    }

    /**
     * Action name.
     */
    public function name(): string
    {
        return __('core::actions.bulk_edit');
    }

    /**
     * Get the component the action should use.
     */
    public function component(): string
    {
        return 'action-bulk-edit';
    }

    /**
     * There is no support for custom message.
     */
    public function message(): string
    {
        return '';
    }
}
