<?php
 

namespace Modules\Core\Tests\Feature\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Modules\Core\App\Models\Media;
use Tests\Fixtures\Event;
use Tests\TestCase;

class MediaModelTest extends TestCase
{
    public function test_it_generates_media_token_on_creation()
    {
        $media = Media::factory()->create(['token' => null]);

        $this->assertNotEmpty($media->token);
    }

    public function test_it_can_purge_mediable_media()
    {
        // With array
        $media = Media::factory()->create();
        $event = Event::factory()->create();
        $event->attachMedia($media, 'tag');

        $media->purgeByMediableIds(Event::class, [$event->id]);

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        $this->assertCount(0, $event->media);

        // With lazy collection
        $media = Media::factory()->create();
        $event = Event::factory()->create();
        $event->attachMedia($media, 'tag');

        $media->purgeByMediableIds(Event::class, new LazyCollection([$event->id]));

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        $this->assertCount(0, $event->media);

        // With regular collection
        $media = Media::factory()->create();
        $event = Event::factory()->create();
        $event->attachMedia($media, 'tag');

        $media->purgeByMediableIds(Event::class, new Collection([$event->id]));

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        $this->assertCount(0, $event->media);
    }

    public function test_it_does_not_make_query_when_pruning_if_the_mediable_ids_count_is_zero()
    {
        $this->assertFalse((new Media())->purgeByMediableIds(Event::class, []));
    }

    public function test_it_can_find_media_by_token()
    {
        $media = Media::factory()->create();

        $this->assertTrue($media->is(Media::byToken($media->token)->first()));
    }

    public function test_it_can_find_media_by_tokens()
    {
        $media = Media::factory()->create();

        $this->assertCount(1, Media::byTokens([$media->token])->get());
    }
}
