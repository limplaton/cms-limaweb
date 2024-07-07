<?php
 

namespace Modules\Users\App\Actions;

use Illuminate\Support\Collection;
use Modules\Core\App\Actions\Action;
use Modules\Core\App\Actions\ActionFields;
use Modules\Core\App\Fields\User;
use Modules\Core\App\Http\Requests\ActionRequest;
use Modules\Core\App\Http\Requests\ResourceRequest;

class AssignOwnerAction extends Action
{
    /**
     * Handle method.
     */
    public function handle(Collection $models, ActionFields $fields): void
    {
        foreach ($models as $model) {
            $model->forceFill([
                $model->user()->getForeignKeyName() => $fields->user_id,
            ])->save();
        }
    }

    /**
     * Get the action fields.
     */
    public function fields(ResourceRequest $request): array
    {
        return [
            User::make(__('users::user.user'))
                ->rules('required')
                ->withoutClearAction(),
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
     * Action name.
     */
    public function name(): string
    {
        return __('users::user.assign');
    }
}
