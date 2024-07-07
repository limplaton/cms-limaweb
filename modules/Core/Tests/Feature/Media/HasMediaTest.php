<?php
 

namespace Modules\Core\Tests\Feature\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Plank\Mediable\Facades\MediaUploader;
use Tests\Fixtures\Post;
use Tests\TestCase;

class HasMediaTest extends TestCase
{
    public function test_it_process_model_attributeable_media_on_creation()
    {
        Storage::fake('local');

        $media = $this->fakeMedia()->upload();
        $pendingMedia = $this->fakePendingMedia();

        $model = $this->modelWithAttributeableMedia($pendingMedia, $media)->create();

        $this->assertCount(2, $model->mediaFromAttributes);
        $this->assertFalse($pendingMedia->isPending());
    }

    public function test_it_process_model_attributeable_media_on_update()
    {
        Storage::fake('local');

        $media1 = $this->fakePendingMedia();
        $media2 = $this->fakePendingMedia();

        $model = $this->modelWithAttributeableMedia($media1, $media2)->create();
        $model->wasRecentlyCreated = false;

        $media3 = $this->fakePendingMedia();
        $model->fill([
            'body' => $model->body.$this->fakeAttributeableMediaText($media3),
        ])->save();

        $this->assertCount(3, $model->mediaFromAttributes);
        $this->assertFalse($media1->isPending());
        $this->assertFalse($media2->isPending());
        $this->assertFalse($media3->isPending());

        $model->fill([
            'body' => $this->fakeAttributeableMediaText($media3),
        ])->save();

        $model = $model->fresh(['mediaFromAttributes']);

        $this->assertCount(1, $model->mediaFromAttributes);
        Storage::assertMissing([$media1->getDiskPath(), $media2->getDiskPath()]);
    }

    public function test_it_properly_deletes_model_attributeable_media_when_used_multiple_times()
    {
        Storage::fake('local');

        $media = $this->fakePendingMedia();

        $model1 = $this->modelWithAttributeableMedia($media)->create();
        $media = $media->fresh();
        $model2 = $this->modelWithAttributeableMedia($media)->create();

        $model1->delete();

        $this->assertCount(1, $model2->mediaFromAttributes);
        $this->assertSame($media->id, $model2->mediaFromAttributes[0]->id);
        $this->assertDatabaseHas('media', ['id' => $media->id]);
        $this->assertDatabaseHas('mediables', ['media_id' => $media->id]);
        $this->assertDatabaseCount('mediables', 1);
        Storage::assertExists($media->getDiskPath());

        $model2->delete();

        Storage::assertMissing($media->getDiskPath());
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        $this->assertDatabaseMissing('mediables', ['media_id' => $media->id]);
        $this->assertDatabaseCount('mediables', 0);
    }

    public function test_it_does_not_delete_attributeable_media_if_used_multiple_times()
    {
        Storage::fake('local');

        $attributeMedia = $this->fakeMedia()->upload();
        $directMedia = $this->fakeMedia()->upload();

        $model1 = $this->modelWithAttributeableMedia($attributeMedia)->create();
        $model1->attachMedia($directMedia, $model1->getMediaTags());

        $this->modelWithAttributeableMedia($attributeMedia)->create();

        $model1->delete();

        Storage::assertMissing($directMedia->getDiskPath());

        $this->assertDatabaseMissing('media', ['id' => $directMedia->id]);
        $this->assertDatabaseMissing('mediables', ['media_id' => $directMedia->id]);

        $this->assertDatabaseHas('media', ['id' => $attributeMedia->id]);
        $this->assertDatabaseHas('mediables', ['media_id' => $attributeMedia->id]);
    }

    public function test_it_does_delete_all_attached_media_when_not_used_elsewhere()
    {
        Storage::fake('local');

        $attributeMedia = $this->fakeMedia()->upload();
        $directMedia = $this->fakeMedia()->upload();

        $model = $this->modelWithAttributeableMedia($attributeMedia)->create();
        $model->attachMedia($directMedia, $model->getMediaTags());

        $model->delete();

        Storage::assertMissing($directMedia->getDiskPath());
        Storage::assertMissing($attributeMedia->getDiskPath());
        $this->assertDatabaseMissing('media', ['id' => $directMedia->id]);
        $this->assertDatabaseMissing('mediables', ['media_id' => $directMedia->id]);
        $this->assertDatabaseMissing('media', ['id' => $attributeMedia->id]);
        $this->assertDatabaseMissing('mediables', ['media_id' => $attributeMedia->id]);
    }

    public function test_it_attaches_the_correct_attributeable_media_tag()
    {
        Storage::fake('local');

        $attributeMedia = $this->fakeMedia()->upload();

        $model = $this->modelWithAttributeableMedia($attributeMedia)->create();

        $this->assertSame($model->getAttributeableMediaTag(), $model->mediaFromAttributes[0]->pivot->tag);
    }

    protected function modelWithAttributeableMedia($media1, $media2 = null)
    {
        return Post::factory([
            'body' => $this->fakeAttributeableMediaText($media1, $media2),
        ]);
    }

    protected function fakeAttributeableMediaText($media1, $media2 = null)
    {
        $text = '<p><img src="/media/'.$media1->token.'" /></p>';

        if ($media2) {
            $text .= '<p><img src="/media/'.$media2->token.'" /></p>';
        }

        return $text;
    }

    protected function fakePendingMedia()
    {
        $media = $this->fakeMedia()->toDirectory('pending-attachments')->upload();
        $media->markAsPending('testDraftId');

        return $media;
    }

    protected function fakeMedia()
    {
        return MediaUploader::fromSource(
            UploadedFile::fake()->image('photo.jpg')
        )->toDisk('local');
    }
}
