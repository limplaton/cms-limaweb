<?php
 

namespace Modules\Core\App\Actions;

use Illuminate\Support\Collection;
use Modules\Core\App\Http\Requests\ActionRequest;

class SearchInGoogleAction extends Action
{
    /**
     * Indicates that this action is without confirmation dialog.
     */
    public bool $withoutConfirmation = true;

    /**
     * Indicates that the action will be hidden on the index view.
     */
    public bool $hideOnIndex = true;

    /**
     * Handle method.
     */
    public function handle(Collection $models, ActionFields $fields): array
    {
        return static::openInNewTab('https://www.google.com/search?q='.urlencode($models->first()->displayName()));
    }

    /**
     * @param  \Illumindate\Database\Eloquent\Model  $model
     */
    public function authorizedToRun(ActionRequest $request, $model): bool
    {
        return $request->user()->can('view', $model);
    }

    /**
     * Action name.
     */
    public function name(): string
    {
        return __('core::actions.search_in_google');
    }
}
