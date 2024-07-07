<?php
 

namespace Modules\MailClient\App\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Modules\Core\App\Actions\ActionFields;
use Modules\Core\App\Actions\DestroyableAction;
use Modules\Core\App\Http\Requests\ActionRequest;
use Modules\MailClient\App\Http\Resources\EmailAccountResource;
use Modules\MailClient\App\Models\EmailAccount;
use Modules\MailClient\App\Services\EmailAccountMessageSyncService;

class EmailAccountMessageDelete extends DestroyableAction
{
    /**
     * Handle method.
     */
    public function handle(Collection $models, ActionFields $fields): array
    {
        $accountId = request()->integer('account_id');

        $service = new EmailAccountMessageSyncService();

        $service->delete($models, request()->integer('folder_id') ?: null);

        $account = EmailAccount::withCommon()->find($accountId);

        return [
            'unread_count' => EmailAccount::countUnreadMessagesForUser(auth()->user()),
            'account' => new EmailAccountResource($account),
            'trash_folder_id' => $account->trashFolder->id,
        ];
    }

    /**
     * @param  \Illumindate\Database\Eloquent\Model  $model
     */
    public function authorizedToRun(ActionRequest $request, $model): bool
    {
        return $request->user()->can('view', $model->account);
    }

    /**
     * Query the models for execution.
     */
    protected function findModelsForExecution(array $ids, Builder $query): EloquentCollection
    {
        return $query->with('account.user')->findMany($ids);
    }

    /**
     * Action name.
     */
    public function name(): string
    {
        return __('core::app.delete');
    }
}
