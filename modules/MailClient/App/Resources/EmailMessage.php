<?php

namespace Modules\MailClient\App\Resources;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\App\Contracts\Resources\Tableable;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Resource\Resource;
use Modules\Core\App\Table\Table;
use Modules\MailClient\App\Client\FolderType;
use Modules\MailClient\App\Criteria\EmailAccountMessageCriteria;
use Modules\MailClient\App\Criteria\EmailAccountMessagesForUserCriteria;
use Modules\MailClient\App\Http\Resources\EmailAccountMessageResource;
use Modules\MailClient\App\Models\EmailAccountMessage;

class EmailMessage extends Resource implements Tableable
{
    /**
     * Indicates whether the resource is globally searchable
     */
    public static bool $globallySearchable = true;

    /**
     * The resource displayable icon.
     */
    public static ?string $icon = 'Mail';

    /**
     * The model the resource is related to
     */
    public static string $model = 'Modules\MailClient\App\Models\EmailAccountMessage';

    /**
     * Provide the criteria that should be used to query only records that the logged-in user is authorized to view
     */
    public function viewAuthorizedRecordsCriteria(): ?string
    {
        return EmailAccountMessagesForUserCriteria::class;
    }

    /**
     * Get the resource search columns.
     */
    public function searchableColumns(): array
    {
        return ['subject' => 'like', 'from.address', 'from.name'];
    }

    /**
     * Get columns that should be used for global search.
     */
    public function globalSearchColumns(): array
    {
        return $this->searchableColumns();
    }

    /**
     * Prepare global search query.
     */
    public function globalSearchQuery(ResourceRequest $request): Builder
    {
        return parent::globalSearchQuery($request)
            ->select(['id', 'subject', 'email_account_id', 'created_at'])
            ->with(['folders', 'account']);
    }

    /**
     * Get the json resource that should be used for json response
     */
    public function jsonResource(): string
    {
        return EmailAccountMessageResource::class;
    }

    /**
     * The resource name
     */
    public static function name(): string
    {
        return 'emails';
    }

    /**
     * Get the resource relationship name when it's associated
     */
    public function associateableName(): string
    {
        return 'emails';
    }

    /**
     * Create the query when the resource is associated and the data is intended for the timeline.
     */
    public function timelineQuery(Model $subject, ResourceRequest $request): Builder
    {
        return parent::timelineQuery($subject, $request)->whereHas('folders.account', function ($query) {
            return $query->whereColumn('folder_id', '!=', 'trash_folder_id');
        });
    }

    /**
     * Provide the resource table class instance.
     */
    public function table(Builder $query, ResourceRequest $request): Table
    {
        $criteria = new EmailAccountMessageCriteria(
            $request->integer('account_id'),
            $request->integer('folder_id')
        );

        if ($request->filled('tag')) {
            $query->withAnyTags($request->input('tag'), EmailAccountMessage::TAGS_TYPE);
        }

        $tableClass = $this->getTableClassByFolderType($request->folder_type);

        return new $tableClass($query->criteria($criteria), $request);
    }

    /**
     * Provides the resource available actions
     */
    public function actions(ResourceRequest $request): array
    {
        return [
            (new \Modules\MailClient\App\Actions\EmailAccountMessageMarkAsRead)->withoutConfirmation(),
            (new \Modules\MailClient\App\Actions\EmailAccountMessageMarkAsUnread)->withoutConfirmation(),
            new \Modules\MailClient\App\Actions\EmailAccountMessageMove,
            new \Modules\MailClient\App\Actions\EmailAccountMessageDelete,
        ];
    }

    /**
     * Get the displayable label of the resource
     */
    public static function label(): string
    {
        return __('mailclient::mail.messages');
    }

    /**
     * Get the displayable singular label of the resource
     */
    public static function singularLabel(): string
    {
        return __('mailclient::mail.message');
    }

    /**
     * Get the table FQCN by given folder type
     */
    protected function getTableClassByFolderType(?string $type): string
    {
        if ($type === FolderType::OTHER || $type == 'incoming') {
            return IncomingMessageTable::class;
        }

        return OutgoingMessageTable::class;
    }
}
