<?php
 

namespace Modules\Deals\App\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Modules\Core\App\Actions\Action;
use Modules\Core\App\Actions\ActionFields;
use Modules\Core\App\Fields\Select;
use Modules\Core\App\Http\Requests\ActionRequest;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Deals\App\Models\Stage;

class ChangeDealStage extends Action
{
    /**
     * Indicates that the action will be hidden on the detail view.
     */
    public bool $hideOnDetail = true;

    /**
     * Handle method.
     */
    public function handle(Collection $models, ActionFields $fields): void
    {
        foreach ($models as $model) {
            $model->forceFill(['stage_id' => $fields->stage_id])->save();
        }
    }

    /**
     * Get the action fields.
     */
    public function fields(ResourceRequest $request): array
    {
        return [
            Select::make('stage_id', __('deals::fields.deals.stage.name'))
                ->labelKey('name')
                ->valueKey('id')
                ->rules('required')
                ->options(function () use ($request) {
                    return Stage::allStagesForOptions($request->user());
                }),
        ];
    }

    /**
     * @param  \Illumindate\Database\Eloquent\Model  $model
     */
    public function authorizedToRun(ActionRequest $request, $model): bool
    {
        return $request->user()->can('update', $model);
    }

    /**
     * Query the models for execution.
     */
    protected function findModelsForExecution(array $ids, Builder $query): EloquentCollection
    {
        return $query->with('stage')->findMany($ids);
    }

    /**
     * Action name.
     */
    public function name(): string
    {
        return __('deals::deal.actions.change_stage');
    }
}
