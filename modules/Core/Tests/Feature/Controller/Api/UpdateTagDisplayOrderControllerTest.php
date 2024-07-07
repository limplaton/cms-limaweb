<?php
 

namespace Modules\Core\Tests\Feature\Controller\Api;

use Modules\Core\App\Models\Tag;
use Tests\TestCase;

class UpdateTagDisplayOrderControllerTest extends TestCase
{
    public function test_unauthenticated_cannot_access_tags_endpoints()
    {
        $this->postJson('/api/tags/order')->assertUnauthorized();
    }

    public function test_unauthorized_cannot_access_tags_endpoints()
    {
        $this->asRegularUser()->signIn();

        $this->postJson('/api/tags/order')->assertForbidden();
    }

    public function test_tags_display_order_can_be_updated()
    {
        $this->signIn();

        $tag1 = Tag::factory()->create(['display_order' => 1]);
        $tag2 = Tag::factory()->create(['display_order' => 2]);

        $data = [
            ['id' => $tag1->id, 'display_order' => 9],
            ['id' => $tag2->id, 'display_order' => 8],
        ];

        $this->postJson('/api/tags/order', $data)->assertNoContent();
        $this->assertDatabaseHas('tags', ['id' => $tag1->id, 'display_order' => 9]);
        $this->assertDatabaseHas('tags', ['id' => $tag2->id, 'display_order' => 8]);
    }

    public function test_tags_update_display_order_is_properly_validated()
    {
        $this->signIn();

        $data = [
            ['id' => null, 'display_order' => null],
        ];

        $this->postJson('/api/tags/order', $data)->assertJsonValidationErrors(['0.id', '0.display_order']);
    }
}
