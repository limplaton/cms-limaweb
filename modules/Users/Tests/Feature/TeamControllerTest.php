<?php
 

namespace Modules\Users\Tests\Feature;

use Modules\Users\App\Models\Team;
use Modules\Users\App\Models\User;
use Tests\TestCase;

class TeamControllerTest extends TestCase
{
    public function test_unauthenticated_cannot_access_teams_endpoints()
    {
        $team = Team::factory()->create();
        $this->getJson('/api/teams')->assertUnauthorized();
        $this->getJson('/api/teams/'.$team->id)->assertUnauthorized();
        $this->postJson('/api/teams')->assertUnauthorized();
        $this->putJson('/api/teams/'.$team->id)->assertUnauthorized();
        $this->deleteJson('/api/teams/'.$team->id)->assertUnauthorized();
    }

    public function test_unauthorized_user_cannot_access_teams_endpoints()
    {
        $team = Team::factory()->create();
        $this->asRegularUser()->signIn();

        $this->postJson('/api/teams')->assertForbidden();
        $this->putJson('/api/teams/'.$team->id)->assertForbidden();
        $this->deleteJson('/api/teams/'.$team->id)->assertForbidden();
    }

    public function test_user_can_retrieve_all_teams()
    {
        $this->signIn();
        Team::factory(2)->create();

        $this->getJson('/api/teams')
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function test_regular_user_can_retrieve_own_teams_only()
    {
        $user = $this->asRegularUser()->signIn();
        $otherUser = $this->createUser();

        Team::factory()->hasAttached($user)->create();
        Team::factory()->hasAttached($otherUser)->create();

        $this->getJson('/api/teams')
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_user_can_create_team()
    {
        $user = $this->signIn();
        $manager = $this->createUser();

        $this->postJson('/api/teams', [
            'name' => 'Team',
            'description' => 'Description',
            'members' => [$user->id],
            'user_id' => $manager->id,
        ])
            ->assertStatus(201)
            ->assertJson([
                'name' => 'Team',
                'description' => 'Description',
                'user_id' => $manager->id,
            ])->assertJson(['manager' => ['id' => $manager->id]])->assertJsonCount(1, 'members');
    }

    public function test_user_can_update_team()
    {
        $user = $this->signIn();
        $manager = $this->createUser();
        $team = Team::factory()->has(User::factory(2))->create();

        $this->putJson(
            '/api/teams/'.$team->id,
            [

                'name' => 'Changed Name',
                'description' => 'Changed Description',
                'user_id' => $manager->id,
                'members' => [
                    $user->id,
                ], ]
        )
            ->assertOk()
            ->assertJson([
                'name' => 'Changed Name',
                'description' => 'Changed Description',
            ])->assertJson(['manager' => ['id' => $manager->id]])->assertJsonCount(1, 'members');
    }

    public function test_user_can_retrieve_team()
    {
        $this->signIn();

        $team = Team::factory()->has(User::factory(2))->create();

        $this->getJson('/api/teams/'.$team->id)->assertJson([
            'id' => $team->id,
            'name' => $team->name,
            'description' => $team->description,
        ])->assertJsonCount(2, 'members')->assertJsonStructure(['created_at', 'updated_at']);
    }

    public function test_regular_user_can_retrieve_own_team_only()
    {
        $user = $this->asRegularUser()->signIn();
        $otherUser = $this->createUser();

        Team::factory()->hasAttached($user)->create();
        $otherTeam = Team::factory()->hasAttached($otherUser)->create();

        $this->getJson('/api/teams/'.$otherTeam->id)
            ->assertForbidden();
    }

    public function test_user_can_delete_team()
    {
        $this->signIn();

        $team = Team::factory()->has(User::factory(2))->create();

        $this->deleteJson('/api/teams/'.$team->id)->assertNoContent();
        $this->assertModelMissing($team);
    }

    public function test_team_requires_name()
    {
        $this->signIn();
        $team = Team::factory()->create();

        $this->postJson('/api/teams', ['name' => ''])
            ->assertJsonValidationErrors(['name']);

        $this->putJson('/api/teams/'.$team->id)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_team_requires_numeric_members()
    {
        $this->signIn();
        $team = Team::factory()->create();

        $this->postJson('/api/teams', ['members' => ['test']])
            ->assertJsonValidationErrors(['members.0']);

        $this->putJson('/api/teams/'.$team->id, ['members' => ['test']])
            ->assertJsonValidationErrors(['members.0']);
    }

    public function test_team_requires_array_members()
    {
        $this->signIn();
        $team = Team::factory()->create();

        $this->postJson('/api/teams', ['members' => 'test'])
            ->assertJsonValidationErrors(['members']);

        $this->putJson('/api/teams/'.$team->id, ['members' => 'test'])
            ->assertJsonValidationErrors(['members']);
    }
}
