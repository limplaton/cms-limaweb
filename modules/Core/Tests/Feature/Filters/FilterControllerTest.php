<?php
 

namespace Modules\Core\Tests\Feature\Filters;

use Modules\Core\App\Models\Filter;
use Tests\TestCase;

class FilterControllerTest extends TestCase
{
    public function test_unauthenticated_user_cannot_access_filters_endpoints()
    {
        $filter = Filter::factory()->create();

        $this->postJson('/api/filters')
            ->assertUnauthorized();
        $this->putJson('/api/filters/'.$filter->id)
            ->assertUnauthorized();
        $this->deleteJson('/api/filters/'.$filter->id)
            ->assertUnauthorized();
        $this->putJson('/api/filters/'.$filter->id.'/FAKE_FILTER_VIEW/default')
            ->assertUnauthorized();
        $this->deleteJson('/api/filters/'.$filter->id.'/FAKE_FILTER_VIEW/default')
            ->assertUnauthorized();
    }

    public function test_user_can_retrieve_table_filters()
    {
        $user = $this->signIn();

        Filter::factory(5)->for($user)->create();

        $this->getJson('/api/filters/users')->assertJsonCount(5);
    }

    public function test_user_can_retrieve_resource_rules()
    {
        $this->signIn();

        $this->getJson('/api/contacts/rules')->assertJsonFragment(['id' => 'first_name']);
    }

    public function test_user_can_create_table_filter()
    {
        $this->signIn();

        $this->postJson('/api/filters', $data = [
            'identifier' => 'users',
            'name' => 'Filter Name',
            'is_shared' => true,
            'rules' => $this->filterRulesPayload(),
        ])->assertJson($data);
    }

    public function test_authorized_user_can_update_table_filter()
    {
        $user = $this->signIn();

        $filter = Filter::factory()->for($user)->create();

        $this->putJson('/api/filters/'.$filter->id, $data = [
            'name' => 'New Filter Name',
            'is_shared' => true,
            'rules' => $this->filterRulesPayload(),
        ])->assertJson($data);
    }

    public function test_unauthorized_user_can_update_table_filter()
    {
        $user = $this->signIn();

        $filter = Filter::factory()->for($user)->create(
            ['name' => $name = 'Filter Name']
        );

        $this->asRegularUser()->signIn();

        $this->putJson('/api/filters/'.$filter->id, [
            'name' => 'New Filter Name',
            'is_shared' => true,
        ])->assertForbidden();

        $this->assertDatabaseHas('filters', [
            'name' => $name,
            'is_shared' => false,
        ]);
    }

    public function test_authorized_user_can_delete_table_filter()
    {
        $user = $this->signIn();

        $filter = Filter::factory()->for($user)->create();

        $this->deleteJson('/api/filters/'.$filter->id);
        $this->assertModelMissing($filter);
    }

    public function test_unauthorized_user_cannot_delete_table_filter()
    {
        $user = $this->signIn();

        $filter = Filter::factory()->for($user)->create();

        // Sign new user
        $this->asRegularUser()->signIn();

        $this->deleteJson('/api/filters/'.$filter->id)
            ->assertForbidden();

        $this->assertDatabaseHas('filters', [
            'id' => $filter->id,
        ]);
    }

    public function test_user_can_set_default_filter()
    {
        $user = $this->signIn();

        $filter = Filter::factory()->create();

        $this->putJson('/api/filters/'.$filter->id.'/dummy-view/default')
            ->assertJsonPath('defaults.0.view', 'dummy-view')
            ->assertJsonPath('defaults.0.user_id', $user->id);
    }

    public function test_user_can_remove_default_filter()
    {
        $user = $this->signIn();

        $filter = Filter::factory()->create()->markAsDefault('dummy-view', $user->id);

        $this->deleteJson('/api/filters/'.$filter->id.'/dummy-view/default');

        $this->assertEmpty($filter->fresh()->defaults);
    }

    public function test_user_can_share_filter()
    {
        $this->signIn();

        $payload = Filter::factory()->shared()->make()->toArray();
        $response = $this->postJson('/api/filters', $payload);
        $filterId = $response->getData()->id;

        $newUser = $this->signIn();
        $response = $this->getJson('/api/users/table/settings')->assertOk();

        $data = $response->getData();

        $this->assertTrue($filterId === $data->filters[0]->id);
        $this->assertFalse($newUser->id === $data->filters[0]->user_id);
    }

    public function test_readonly_filter_cannot_be_updated_or_deleted()
    {
        $this->signIn();

        $filter = Filter::factory()->create();

        $this->putJson('/api/filters/'.$filter->id, [
            'name' => 'New Filter Name',
            'is_shared' => false,
        ])->assertForbidden();

        $this->deleteJson('/api/filters/'.$filter->id)->assertForbidden();
    }

    public function test_system_default_filter_cannot_be_updated_or_deleted()
    {
        $this->signIn();

        $filter = Filter::factory()->create(['user_id' => null]);

        $this->putJson('/api/filters/'.$filter->id, [
            'name' => 'New Filter Name',
            'is_shared' => false,
        ])->assertForbidden();

        $this->deleteJson('/api/filters/'.$filter->id)->assertForbidden();
    }

    public function test_filter_can_be_system_default_when_no_user()
    {
        $filter = Filter::factory()->create(['user_id' => null]);

        return $this->assertTrue($filter->is_system_default);
    }

    protected function filterRulesPayload()
    {
        return [
            'condition' => 'and',
            'children' => [[
                'type' => 'rule',
                'query' => [
                    'type' => 'text',
                    'operator' => 'equal',
                    'rule' => 'dummy_attribute',
                    'operand' => null,
                    'value' => 'Dummy Value',
                ],
            ]],
        ];
    }
}
