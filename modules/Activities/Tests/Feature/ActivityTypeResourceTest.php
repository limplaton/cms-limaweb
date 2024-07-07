<?php
 

namespace Modules\Activities\Tests\Feature;

use Modules\Core\Tests\ResourceTestCase;

class ActivityTypeResourceTest extends ResourceTestCase
{
    protected $resourceName = 'activity-types';

    public function test_user_can_create_resource_record()
    {
        $this->signIn();

        $this->postJson($this->createEndpoint(), ['name' => 'Aviation', 'swatch_color' => '#ffffff', 'icon' => 'ICON'])
            ->assertStatus(201)
            ->assertJson(['name' => 'Aviation', 'swatch_color' => '#ffffff', 'icon' => 'ICON']);
    }

    public function test_unauthorized_user_cannot_create_resource_record()
    {
        $this->asRegularUser()->signIn();
        $this->postJson($this->createEndpoint(), ['name' => 'Aviation', 'icon' => 'ICON'])->assertForbidden();
    }

    public function test_user_can_update_resource_record()
    {
        $this->signIn();

        $record = $this->factory()->create(['swatch_color' => '#ffffff']);

        $this->putJson($this->updateEndpoint($record), [
            'name' => 'Changed',
            'swatch_color' => '#f1f1f1',
            'icon' => 'ICON',
        ])->assertOk()
            ->assertJson(['name' => 'Changed', 'swatch_color' => '#f1f1f1', 'icon' => 'ICON']);
    }

    public function test_unauthorized_user_cannot_update_resource_record()
    {
        $this->asRegularUser()->signIn();

        $record = $this->factory()->create();

        $this->putJson($this->updateEndpoint($record), [
            'name' => 'Changed',
            'icon' => 'ICON',
        ])->assertForbidden();
    }

    public function test_user_can_retrieve_resource_records()
    {
        $this->signIn();

        $this->factory()->count(5)->create();

        $this->getJson($this->indexEndpoint())->assertJsonCount(5, 'data');
    }

    public function test_user_can_retrieve_resource_record()
    {
        $this->signIn();

        $record = $this->factory()->create();

        $this->getJson($this->showEndpoint($record))->assertOk();
    }

    public function test_user_can_delete_resource_record()
    {
        $this->signIn();

        $record = $this->factory()->create();

        $this->deleteJson($this->deleteEndpoint($record))->assertNoContent();
    }

    public function test_unauthorized_user_cannot_delete_resource_record()
    {
        $this->asRegularUser()->signIn();

        $record = $this->factory()->create();

        $this->deleteJson($this->deleteEndpoint($record))->assertForbidden();
    }

    public function test_activity_type_requires_name()
    {
        $this->signIn();

        $this->postJson($this->createEndpoint(), ['name' => ''])->assertJsonValidationErrors(['name']);

        $record = $this->factory()->create();

        $this->putJson($this->updateEndpoint($record))->assertJsonValidationErrors(['name']);
    }

    public function test_activity_type_name_must_be_unique()
    {
        $this->signIn();

        $records = $this->factory()->count(2)->create();

        $this->postJson(
            $this->createEndpoint(),
            ['name' => $records->first()->name,
            ]
        )->assertJsonValidationErrors(['name']);

        $this->putJson(
            $this->updateEndpoint($records->get(1)),
            ['name' => $records->first()->name]
        )->assertJsonValidationErrors(['name']);
    }

    public function test_activity_type_requires_icon()
    {
        $this->signIn();

        $this->postJson($this->createEndpoint(), ['icon' => ''])->assertJsonValidationErrors(['icon']);

        $record = $this->factory()->create();

        $this->putJson($this->updateEndpoint($record))->assertJsonValidationErrors(['icon']);
    }

    public function test_activity_type_icon_must_be_unique()
    {
        $this->signIn();

        $records = $this->factory()->count(2)->create();

        $this->postJson(
            $this->createEndpoint(),
            ['icon' => $records->first()->icon,
            ]
        )->assertJsonValidationErrors(['icon']);

        $this->putJson(
            $this->updateEndpoint($records->get(1)),
            ['icon' => $records->first()->icon]
        )->assertJsonValidationErrors(['icon']);
    }
}
