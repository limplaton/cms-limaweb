<?php
 

namespace Modules\Core\App\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;
use Modules\Core\App\Http\Requests\ActionRequest;

class DeleteAction extends DestroyableAction
{
    /**
     * Action authorization callback.
     */
    public $authorizeToRunCallback = null;

    /**
     * Handle method.
     */
    public function handle(Collection $models, ActionFields $fields)
    {
        DB::transaction(function () use ($models) {
            foreach ($models as $model) {
                $model->delete();
            }
        });
    }

    /**
     * Determine if the action is executable for the given request.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function authorizedToRun(ActionRequest $request, $model): bool
    {
        if (is_callable($this->authorizeToRunCallback)) {
            return call_user_func_array($this->authorizeToRunCallback, [$request, $model, $this->total]);
        }

        return throw new LogicException('Provide authorization for the "delete" action.');
    }

    /**
     * Set authorization callback for the action.
     */
    public function authorizedToRunWhen(callable $callback): static
    {
        $this->authorizeToRunCallback = $callback;

        return $this;
    }

    /**
     * Provide action human readable name.
     */
    public function name(): string
    {
        return $this->name ?: __('core::app.delete');
    }
}
