<?php
 

namespace Modules\Core\App\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\App\Http\Requests\ActionRequest;

class ForceDeleteAction extends DestroyableAction
{
    /**
     * Handle method.
     */
    public function handle(Collection $models, ActionFields $fields)
    {
        DB::transaction(function () use ($models) {
            foreach ($models as $model) {
                $model->forceDelete();
            }
        });
    }

    /**
     * @param  \Illumindate\Database\Eloquent\Model  $model
     */
    public function authorizedToRun(ActionRequest $request, $model): bool
    {
        return $request->user()->can('bulkDelete', $model);
    }

    /**
     * Query the models for execution.
     */
    protected function findModelsForExecution(array $ids, Builder $query): EloquentCollection
    {
        return $query->withTrashed()->findMany($ids);
    }

    /**
     * Action name.
     */
    public function name(): string
    {
        return __('core::app.soft_deletes.force_delete');
    }
}
