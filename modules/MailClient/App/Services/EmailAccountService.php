<?php
 

namespace Modules\MailClient\App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Arr;
use Modules\MailClient\App\Client\ConnectionType;
use Modules\MailClient\App\Client\FolderCollection;
use Modules\MailClient\App\Models\EmailAccount;
use Modules\MailClient\App\Models\EmailAccountFolder;

class EmailAccountService
{
    public function create(array $attributes): EmailAccount
    {
        $model = new EmailAccount($attributes);

        // If user exists, mark the account as personal before save
        if (isset($attributes['user_id'])) {
            $model->forceFill(['user_id' => $attributes['user_id']]);
        }

        $model->save();

        $model->setMeta(
            'from_name_header',
            ($attributes['from_name_header'] ?? '') ?: EmailAccount::DEFAULT_FROM_NAME_HEADER
        );

        foreach ($attributes['folders'] ?? [] as $folder) {
            $this->saveFolder($model, $folder);
        }

        foreach (['trash', 'sent'] as $folderType) {
            if ($folder = $model->folders->firstWhere('type', $folderType)) {
                tap($model, function ($instance) use ($folder, $folderType) {
                    $instance->{$folderType.'Folder'}()->associate($folder);
                })->save();
            }
        }

        return $model;
    }

    public function update(EmailAccount $model, array $attributes): EmailAccount
    {
        // This may happen if for some reason Laravel failed to decrypt an IMAP account password
        // The user may provide new password via account update, however, as Laravel is accessing
        // the attributes to store their original value, will throw an exception on the original invalid password,
        // in this case if this happens, we will remove the password from the attributes array and re-fill the model
        // again with the newly provided password from the user.
        if ($oldPassword = $model->getRawOriginal('password')) {
            try {
                $model->fromEncryptedString($oldPassword);
            } catch (DecryptException) {
                $modelAttributes = $model->getAttributes();
                Arr::forget($modelAttributes, 'password');
                $model->setRawAttributes($modelAttributes, true);
            }
        }

        $model->fill($attributes)->save();

        if (isset($attributes['from_name_header'])) {
            $model->setMeta('from_name_header', $attributes['from_name_header']);
        }

        foreach ($attributes['folders'] ?? [] as $folder) {
            $this->saveFolder($model, $folder);
        }

        return $model;
    }

    /**
     * Save the given folder for the account.
     */
    public function saveFolder(EmailAccount $account, array $folder): EmailAccountFolder
    {
        $parent = EmailAccountFolder::updateOrCreate(
            $this->getUpdateOrCreateAttributes($account, $folder),
            array_merge($folder, [
                'email_account_id' => $account->id,
                'syncable' => $folder['syncable'] ?? false,
            ])
        );

        $this->handleChildFolders($parent, $folder, $account);

        return $parent;
    }

    /**
     * Handle the child folders creation process
     */
    protected function handleChildFolders(EmailAccountFolder $parentFolder, array $folder, EmailAccount $account): void
    {
        // Avoid errors if the children key is not set
        if (! isset($folder['children'])) {
            return;
        }

        if ($folder['children'] instanceof FolderCollection) {
            /**
             * @see \Modules\MailClient\App\Listeners\CreateEmailAccountViaOAuth
             */
            $folder['children'] = $folder['children']->toArray();
        }

        foreach ($folder['children'] as $child) {
            $parent = $this->saveFolder($account, array_merge($child, [
                'parent_id' => $parentFolder->id,
            ]));

            $this->handleChildFolders($parent, $child, $account);
        }
    }

    /**
     * Get the attributes that should be used for update or create method.
     */
    protected function getUpdateOrCreateAttributes(EmailAccount $account, array $folder): array
    {
        $attributes = ['email_account_id' => $account->id];

        // If the folder database ID is passed
        // use the ID as unique identifier for the folder
        if (isset($folder['id'])) {
            $attributes['id'] = $folder['id'];
        } else {
            // For imap account, we use the name as unique identifier
            // as the remote_id may not always be unique
            if ($account->connection_type === ConnectionType::Imap) {
                $attributes['name'] = $folder['name'];
            } else {
                // For API based accounts e.q. Gmail and Outlook
                // we use the remote_id as unique identifier
                $attributes['remote_id'] = $folder['remote_id'];
            }
        }

        return $attributes;
    }
}
