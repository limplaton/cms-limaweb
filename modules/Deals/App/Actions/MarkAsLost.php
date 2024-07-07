<?php
 

namespace Modules\Deals\App\Actions;

use Illuminate\Support\Collection;
use Modules\Core\App\Actions\Action;
use Modules\Core\App\Actions\ActionFields;
use Modules\Core\App\Http\Requests\ActionRequest;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Rules\StringRule;
use Modules\Deals\App\Fields\LostReasonField;
use Modules\Deals\App\Models\Deal;

class MarkAsLost extends Action
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
        $models->reject(fn (Deal $model) => $model->isLost())->each(function (Deal $model) use ($fields) {
            $model->markAsLost($fields->lost_reason);
        });
    }

    /**
     * Get the action fields.
     */
    public function fields(ResourceRequest $request): array
    {
        return [
            LostReasonField::make('lost_reason', __('deals::deal.lost_reasons.lost_reason'))->rules(
                (bool) settings('lost_reason_is_required') ? 'required' : 'nullable',
                StringRule::make(),
            ),
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
        return __('deals::deal.actions.mark_as_lost');
    }
}
