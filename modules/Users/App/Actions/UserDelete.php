<?php
 

namespace Modules\Users\App\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\App\Actions\ActionFields;
use Modules\Core\App\Actions\DestroyableAction;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Fields\User;
use Modules\Core\App\Http\Requests\ActionRequest;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Resource\Events\ResourceRecordDeleted;
use Modules\Users\App\Models\User as UserModel;
use Modules\Users\App\Services\UserService;

class UserDelete extends DestroyableAction
{
    /**
     * Handle method.
     */
    public function handle(Collection $models, ActionFields $fields): void
    {
        // User delete action flag
        $service = new UserService;

        // Make sure to position the ID of the current user as the first one in the list.
        // This way, if there's an issue, the "delete" method in the service will fail early.
        $currentUser = $models->first(fn (UserModel $user) => $user->is(Auth::user()));

        if ($currentUser) {
            $models = $models->reject(fn (UserModel $user) => $user->is(Auth::user()))->prepend($currentUser);
        }

        $resource = Innoclapps::resourceByModel(UserModel::class);

        DB::transaction(function () use ($models, $fields, $service, $resource) {
            foreach ($models as $model) {
                $service->delete($model, (int) $fields->user_id);

                ResourceRecordDeleted::dispatch($model, $resource);
            }
        });
    }

    /**
     * Query the models for execution.
     */
    protected function findModelsForExecution(array $ids, Builder $query): EloquentCollection
    {
        return $query->with(
            ['personalEmailAccounts', 'oAuthAccounts', 'connectedCalendars', 'comments', 'imports']
        )->findMany($ids);
    }

    /**
     * Get the action fields
     */
    public function fields(ResourceRequest $request): array
    {
        return [
            User::make('')
                ->help(__('users::user.transfer_data_info'))
                ->helpDisplay('text')
                ->rules('required'),
        ];
    }

    /**
     * @param  \Illumindate\Database\Eloquent\Model  $model
     */
    public function authorizedToRun(ActionRequest $request, $model): bool
    {
        return $request->user()->isSuperAdmin();
    }

    /**
     * Action name
     */
    public function name(): string
    {
        return __('users::user.actions.delete');
    }
}
