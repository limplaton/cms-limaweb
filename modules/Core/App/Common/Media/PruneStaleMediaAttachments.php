<?php
 

namespace Modules\Core\App\Common\Media;

use Modules\Core\App\Models\PendingMedia;

class PruneStaleMediaAttachments
{
    /**
     * Prune the stale attached media from the system.
     */
    public function __invoke(): void
    {
        PendingMedia::with('attachment')
            ->orderByDesc('id')
            ->where('created_at', '<=', now()->subDays(1))
            ->get()
            ->each(function (PendingMedia $media) {
                $media->purge();
            });
    }
}
