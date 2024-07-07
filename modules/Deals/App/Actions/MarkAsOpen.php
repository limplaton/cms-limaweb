<?php
 

namespace Modules\Deals\App\Actions;

use Illuminate\Support\Collection;
use Modules\Core\App\Actions\Action;
use Modules\Core\App\Actions\ActionFields;
use Modules\Core\App\Http\Requests\ActionRequest;
use Modules\Deals\App\Models\Deal;

class MarkAsOpen extends Action
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
        $models->reject(fn (Deal $model) => $model->isOpen())->each(function (Deal $model) {
            $model->markAsOpen();
        });
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
        return __('deals::deal.actions.mark_as_open');
    }
}
