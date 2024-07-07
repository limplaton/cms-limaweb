<?php
 

namespace Modules\MailClient\App\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Modules\Core\App\Actions\Action;
use Modules\Core\App\Actions\ActionFields;
use Modules\Core\App\Fields\Select;
use Modules\Core\App\Http\Requests\ActionRequest;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\MailClient\App\Http\Resources\EmailAccountResource;
use Modules\MailClient\App\Models\EmailAccount;
use Modules\MailClient\App\Models\EmailAccountFolder;
use Modules\MailClient\App\Services\EmailAccountMessageService;

class EmailAccountMessageMove extends Action
{
    /**
     * Handle method.
     */
    public function handle(Collection $models, ActionFields $fields): array
    {
        $accountId = request()->integer('account_id');

        $service = new EmailAccountMessageService();

        $service->batchMoveTo(
            $models,
            $fields->move_to_folder_id,
            request()->integer('folder_id') ?: null
        );

        $account = EmailAccount::withCommon()->find($accountId);

        return [
            'unread_count' => EmailAccount::countUnreadMessagesForUser(auth()->user()),
            'account' => new EmailAccountResource($account),
            'moved_to_folder_id' => $fields->move_to_folder_id,
        ];
    }

    /**
     * Get the action fields.
     */
    public function fields(ResourceRequest $request): array
    {
        return [
            Select::make('move_to_folder_id')
                ->labelKey('display_name')
                ->valueKey('id')
                ->rules('required')
                ->options(function () use ($request) {
                    return EmailAccountFolder::where('email_account_id', $request->integer('account_id'))
                        ->get()
                        ->filter(function ($folder) {
                            return $folder->support_move;
                        });
                }),
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
        return __('mailclient::inbox.move_to');
    }
}
