<?php
 

namespace Modules\Documents\App\Observers;

use Modules\Documents\App\Models\Document;

class DocumentObserver
{
    /**
     * Handle the Document "created" event.
     */
    public function created(Document $document): void
    {
        $document->addActivity([
            'lang' => [
                'key' => 'documents::document.activity.created',
                'attrs' => [
                    // for unit tests
                    'user' => auth()->user()?->name,
                ],
            ],
        ]);
    }

    /**
     * Handle the Document "deleting" event.
     */
    public function deleting(Document $document): void
    {
        if ($document->isForceDeleting()) {
            $document->purge();
        }
    }
}
