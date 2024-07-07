<?php
 

namespace Modules\Core\Tests\Feature;

use Illuminate\Support\Carbon;
use Modules\Core\App\Common\Media\PruneStaleMediaAttachments;
use Modules\Core\App\Models\Media;
use Tests\TestCase;

class PruneStaleMediaAttachmentsTest extends TestCase
{
    public function test_it_prunes_stale_media_attachments()
    {
        Carbon::setTestNow(now()->subDay(1)->startOfDay());
        $media = Media::factory()->create();

        $pendingMedia = $media->markAsPending('draft-id');

        Carbon::setTestNow(null);

        (new PruneStaleMediaAttachments)();

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        $this->assertDatabaseMissing('pending_media_attachments', ['id' => $pendingMedia->id]);
    }
}
