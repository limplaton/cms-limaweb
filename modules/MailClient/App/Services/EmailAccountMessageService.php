<?php
 

namespace Modules\MailClient\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\MailClient\App\Models\EmailAccount;
use Modules\MailClient\App\Models\EmailAccountFolder;
use Modules\MailClient\App\Models\EmailAccountMessage;

class EmailAccountMessageService
{
    /**
     * Batch move messages to a given folder
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $messages
     * @param  int  $to
     * @param  null|int  $from
     * @return void
     */
    public function batchMoveTo($messages, $to, $from = null)
    {
        $messages->loadMissing('folders');

        $allAccounts = EmailAccount::with(['oAuthAccount', 'user'])->get();
        $allFolders = EmailAccountFolder::with('account')->get();

        $to = $allFolders->find($to);

        $messagesByAccount = $messages->groupBy('email_account_id');

        foreach ($messagesByAccount as $accountId => $accountMessages) {
            $messagesByFromFolder = $accountMessages->groupBy(
                fn ($message) => $from ? $message->folders->find($from)->id : $message->folders->first()->id
            )->reject(
                fn ($messages, $fromFolderId) => $allFolders->find($fromFolderId)->is($to) || $to->support_move === false
            );

            if ($messagesByFromFolder->isNotEmpty()) {
                $client = $allAccounts->find($accountId)->getClient();
                $remoteFolders = $client->getFolders();

                // We will use the first message to get the FROM folder
                // as the messages are grouped by FROM, for the rest messages
                // the FROM folder will be the same
                $from = $allFolders->find($messagesByFromFolder->keys()->first());

                foreach ($messagesByFromFolder as $messages) {
                    $maps = $client->batchMoveMessages(
                        $messages->pluck('remote_id')->all(),
                        $remoteFolders->find($to->identifier()),
                        $remoteFolders->find($from->identifier())
                    );

                    foreach ($messages as $message) {
                        // Maps of old => new values exists, in this case, update the current
                        // messages with the new remote_id's to avoid any syncing errors
                        if (is_array($maps)) {
                            // This will help to not delete the message from database
                            // because it's removed
                            if (array_key_exists($message->remote_id, $maps)) {
                                $message->remote_id = $maps[$message->remote_id];
                                $message->save();
                            }
                        }

                        // Since messages can belong to multiple folders e.q. for Gmail
                        // We need to remove the FROM folder from the current folders
                        // and push the new folder
                        $message->folders()
                            ->sync(
                                $message->folders->reject(
                                    fn ($folder) => $folder->id == $from->id
                                )->push($to)
                            );
                    }
                }
            }
        }
    }

    /**
     * Batch mark a messages as read
     *
     * @param  \Illuminate\Support\Collection  $messages
     * @param  int  $accountId
     * @param  int  $folderId
     * @return void
     */
    public function batchMarkAsRead($messages, $accountId, $folderId)
    {
        $account = EmailAccount::find($accountId);

        $messages = $messages->reject(fn ($message) => $message->is_read === true)->values();

        if ($messages->isEmpty()) {
            return;
        }

        $account->createClient()->batchMarkAsRead(
            $messages->pluck('remote_id')->all(),
            EmailAccountFolder::find($folderId)->identifier()
        );

        EmailAccountMessage::whereIn('id', $messages->pluck('id')->all())->update(['is_read' => true]);
    }

    /**
     * Mark a message as read
     *
     * @param  \Illuminate\Support\Collection  $messages
     * @param  int  $accountId
     * @param  int  $folderId
     * @return bool
     */
    public function batchMarkAsUnread($messages, $accountId, $folderId)
    {
        $account = EmailAccount::find($accountId);

        $messages = $messages->reject(fn ($message) => $message->is_read === false)->values();

        if ($messages->isEmpty()) {
            return;
        }

        $account->createClient()->batchMarkAsUnread(
            $messages->pluck('remote_id')->all(),
            EmailAccountFolder::find($folderId)->identifier()
        );

        EmailAccountMessage::whereIn('id', $messages->pluck('id')->all())->update(['is_read' => false]);
    }

    /**
     * Parmanently delete given messages
     *
     * @param \Illuminate\Support\Collection
     * @return void
     */
    public function batchDelete($messages)
    {
        $allAccounts = EmailAccount::with(['user', 'oAuthAccount', 'trashFolder.account'])->get();
        $messagesByAccount = $messages->groupBy('email_account_id');

        $messagesByAccount->each(function ($messages, $accountId) use ($allAccounts) {
            $account = $allAccounts->find($accountId);
            $client = $account->getClient();

            $client->setTrashFolder($client->getFolders()->find($account->trashFolder->identifier()))
                ->batchDeleteMessages($messages->pluck('remote_id')->all());

            $messages->each->delete();
        });
    }

    /**
     * Mark messages as read by remote ids
     *
     * @param  int  $folderId  The folder id to not prevent conflicts in case of same remote uid's
     * @return bool
     */
    public function markAsReadByRemoteIds($folderId, array $remoteIds)
    {
        return EmailAccountMessage::whereRemoteIdsIn($remoteIds)
            ->whereHas('folders', fn ($subQuery) => $subQuery->where('folder_id', $folderId))
            ->update(['is_read' => true]);
    }

    /**
     * Mark messages as unread by remote ids
     *
     * @param  int  $folderId  The folder id to not prevent conflicts in case of same remote uid's
     * @return bool
     */
    public function markAsUnreadByRemoteIds($folderId, array $remoteIds)
    {
        return EmailAccountMessage::whereRemoteIdsIn($remoteIds)
            ->whereHas('folders', fn ($subQuery) => $subQuery->where('folder_id', $folderId))
            ->update(['is_read' => false]);
    }

    /**
     * Find the last synced uid by folder id
     * This is applied only for IMAP account as their last uid
     * may be guaranteed to be integer
     *
     * @param  int  $folderId
     * @return null|int
     */
    public function getLastUidByImapAccountByFolder($folderId)
    {
        $result = EmailAccountMessage::select('remote_id')
            ->ofFolder($folderId)
            ->orderBy(DB::raw('CAST(remote_id AS UNSIGNED)'), 'DESC')
            ->first();

        return $result->remote_id ?? null;
    }

    /**
     * Get database uids for a given folder
     *
     * @param  int  $folderId
     * @param  array  $columns
     * @return \Illuminate\Collections\LazyCollection
     */
    public function getUidsByFolder($folderId, $columns = ['remote_id'])
    {
        return EmailAccountMessage::select($columns)->ofFolder($folderId)->cursor();
    }

    /**
     * Get database uids for a given folder
     *
     * @param  int  $accountId
     * @param  array  $columns
     * @return \Illuminate\Collections\LazyCollection
     */
    public function getUidsByAccount($accountId, $columns = ['remote_id'])
    {
        return EmailAccountMessage::select($columns)->where('email_account_id', $accountId)->cursor();
    }
}
