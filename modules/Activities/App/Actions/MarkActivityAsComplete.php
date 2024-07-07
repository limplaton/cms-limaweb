<?php
 

namespace Modules\Activities\App\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Modules\Core\App\Actions\Action;
use Modules\Core\App\Actions\ActionFields;
use Modules\Core\App\Http\Requests\ActionRequest;

class MarkActivityAsComplete extends Action
{
    /**
     * Indicates that the action does not have confirmation dialog.
     */
    public bool $withoutConfirmation = true;

    /**
     * Handle method.
     */
    public function handle(Collection $models, ActionFields $fields): void
    {
        foreach ($models as $model) {
            $model->markAsComplete();
        }
    }

    /**
     * @param  \Illumindate\Database\Eloquent\Model  $model
     */
    public function authorizedToRun(ActionRequest $request, $model): bool
    {
        return $request->user()->can('update', $model);
    }

    /**
     * Query the models for execution
     */
    protected function findModelsForExecution(array $ids, Builder $query): EloquentCollection
    {
        return $query->with('user')->findMany($ids);
    }

    /**
     * Action name.
     */
    public function name(): string
    {
        return __('activities::activity.mark_as_completed');
    }
}
