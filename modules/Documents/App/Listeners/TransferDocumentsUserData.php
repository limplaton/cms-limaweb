<?php
 

namespace Modules\Documents\App\Listeners;

use Modules\Documents\App\Models\Document;
use Modules\Documents\App\Models\DocumentTemplate;
use Modules\Users\App\Events\TransferringUserData;

class TransferDocumentsUserData
{
    /**
     * Handle the event.
     */
    public function handle(TransferringUserData $event): void
    {
        $this->documents($event->toUserId, $event->fromUserId);
        $this->documentTemplates($event->toUserId, $event->fromUserId);
    }

    /**
     * Transfer documents.
     */
    public function documents($toUserId, $fromUserId): void
    {
        Document::withTrashed()->where('created_by', $fromUserId)->update(['created_by' => $toUserId]);
        Document::withTrashed()->where('user_id', $fromUserId)->update(['user_id' => $toUserId]);
        Document::withTrashed()->where('sent_by', $fromUserId)->update(['sent_by' => $toUserId]);
        Document::withTrashed()->where('marked_accepted_by', $fromUserId)->update(['marked_accepted_by' => $toUserId]);
        Document::withTrashed()->where('approved_by', $fromUserId)->update(['approved_by' => $toUserId]);
    }

    /**
     * Transfer shared document templates.
     */
    public function documentTemplates($toUserId, $fromUserId): void
    {
        DocumentTemplate::where('user_id', $fromUserId)->shared()->update(['user_id' => $toUserId]);
    }
}
