<?php
 

namespace Modules\Core\Tests\Feature\Controller\Api;

use Modules\Core\App\Models\Dashboard;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    public function test_unauthenticated_cannot_access_dashboard_endpoints()
    {
        $this->getJson('/api/dashboard')->assertUnauthorized();
        $this->getJson('/api/dashboard/FAKE_ID')->assertUnauthorized();
        $this->postJson('/api/dashboard')->assertUnauthorized();
        $this->putJson('/api/dashboard/FAKE_ID')->assertUnauthorized();
        $this->deleteJson('/api/dashboard/FAKE_ID')->assertUnauthorized();
    }

    public function test_user_can_create_new_dashboard()
    {
        $this->signIn();

        $this->postJson('/api/dashboards', [
            'name' => 'Test',
            'is_default' => true,
        ])->assertJson(['name' => 'Test', 'is_default' => true]);
    }

    public function test_other_dashboard_are_unmarked_as_default_when_new_default_dashboard_created()
    {
        $user = $this->signIn();
        $defaultNow = $user->dashboards->firstWhere('is_default', true);

        $this->postJson('/api/dashboards', [
            'name' => 'Test',
            'is_default' => true,
        ]);

        $this->assertNotEquals(
            $defaultNow->id,
            $user->dashboards()->get()->firstWhere('is_default', true)
        );
    }

    public function test_it_attaches_default_cards_to_dashboard_when_no_cards_provided()
    {
        $this->signIn();

        $this->postJson('/api/dashboards', [
            'name' => 'Test',
        ])->assertJsonCount(count(Dashboard::defaultCards()->all()), 'cards');
    }

    public function test_dashboard_can_have_cards()
    {
        $this->signIn();

        $this->postJson('/api/dashboards', [
            'name' => 'Test',
            'cards' => [['key' => 'user-delete', 'order' => 1000]],
        ])->assertJsonCount(1, 'cards');
    }

    public function test_dashboard_requires_name()
    {
        $this->signIn();

        $this->postJson('/api/dashboards', [
            'name' => '',
        ])->assertJsonValidationErrorFor('name');

        $dashboard = Dashboard::factory()->create();

        $this->putJson("/api/dashboards/$dashboard->id", [
            'name' => '',
        ])->assertJsonValidationErrorFor('name');
    }

    public function test_dashboard_can_be_retrieved()
    {
        $user = $this->signIn();
        $dashboard = $user->dashboards->first();

        $this->getJson('/api/dashboards/'.$dashboard->id)
            ->assertOk()
            ->assertJson(['id' => $dashboard->id]);
    }

    public function test_user_can_retrieve_only_his_own_dashboard()
    {
        $this->asRegularUser()->signIn();
        $otherUser = $this->createUser();

        $this->getJson('/api/dashboards/'.$otherUser->dashboards->first()->id)
            ->assertForbidden();
    }

    public function test_user_can_retrieve_dashboard()
    {
        $user = $this->asRegularUser()->signIn();

        $this->getJson('/api/dashboards')
            ->assertOk()
            ->assertJsonCount($user->dashboards->count());
    }

    public function test_authorized_user_can_update_dashboard()
    {
        $user = $this->signIn();

        $dashboard = Dashboard::factory()->for($user)->create(['cards' => [
            ['key' => 'card-key', 'order' => 1],
        ]]);

        $this->putJson('/api/dashboards/'.$dashboard->id, [
            'name' => 'Updated Name',
            'is_default' => true,
            'cards' => [['key' => 'card-key', 'order' => 1000]],
        ])->assertOk()
            ->assertJson(['name' => 'Updated Name', 'is_default' => true])
            ->assertJsonPath('cards.0.order', 1000);
    }

    public function test_unauthorized_user_cannot_update_dashboard()
    {
        $this->asRegularUser()->signIn();

        $otherUser = $this->createUser();
        $dashboard = $otherUser->dashboards->first();

        $this->putJson('/api/dashboards/'.$dashboard->id, [
            'name' => 'Updated Name',
        ])->assertForbidden();
    }

    public function test_dashboard_name_is_not_required_on_update_if_not_provided()
    {
        $user = $this->signIn();
        $dashboard = $user->dashboards->first();

        $this->putJson('/api/dashboards/'.$dashboard->id, [
            'cards' => [['key' => 'user-delete', 'order' => 1000]],
        ])->assertJsonMissingValidationErrors(['name'])
            ->assertJson(['name' => $dashboard->name]);
    }

    public function test_authorized_user_can_delete_dashboard()
    {
        $user = $this->signIn();

        $dashboard = Dashboard::factory()->for($user)->create();

        $this->deleteJson("/api/dashboards/$dashboard->id")->assertStatus(204);
    }

    public function test_unauthorized_user_cannot_delete_dashboard()
    {
        $this->asRegularUser()->signIn();

        $otherUser = $this->createUser();
        $dashboard = Dashboard::factory()->for($otherUser)->create();

        $this->deleteJson("/api/dashboards/$dashboard->id")->assertForbidden();
    }

    public function test_when_updating_all_other_dashboard_are_unmarked_as_default_when_new_default_dashboard_is_provided()
    {
        $user = $this->signIn();

        $default = $user->dashboards->firstWhere('is_default', 1);

        $dashboard = Dashboard::factory()->for($user)->create();

        $this->putJson('/api/dashboards/'.$dashboard->id, [
            'is_default' => true,
        ]);

        $defaultNow = $user->dashboards()->get()->firstWhere('is_default', true);

        $this->assertNotNull($defaultNow);
        $this->assertEquals($dashboard->id, $defaultNow->id);
        $this->assertNotEquals($default->id, $defaultNow->id);
    }

    public function test_last_user_dashboard_cannot_be_unmarked_as_default()
    {
        $user = $this->signIn();
        $default = $user->dashboards->firstWhere('is_default', 1);

        $this->putJson('/api/dashboards/'.$default->id, [
            'is_default' => false,
        ])->assertJson(['is_default' => true]);

        $defaultNow = $user->dashboards()->get()->firstWhere('is_default', 1);
        $this->assertNotNull($defaultNow);
        $this->assertSame($default->id, $defaultNow->id);
    }

    public function test_last_user_dashboard_cannot_be_deleted()
    {
        $user = $this->signIn();
        $dashboard = $user->dashboards->first();

        $this->deleteJson('/api/dashboards/'.$dashboard->id)->assertStatus(409);
    }
}
