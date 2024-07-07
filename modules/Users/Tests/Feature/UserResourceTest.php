<?php
 

namespace Modules\Users\Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Modules\Core\App\Models\Filter;
use Modules\Core\Database\Seeders\PermissionsSeeder;
use Modules\Core\Database\State\DatabaseState;
use Modules\Core\Tests\ResourceTestCase;
use Modules\Deals\App\Board\Board;
use Modules\Users\App\Models\Team;
use Modules\Users\App\Models\User;

class UserResourceTest extends ResourceTestCase
{
    protected $resourceName = 'users';

    public function test_user_can_create_resource_record()
    {
        $this->seed(PermissionsSeeder::class);
        $this->signIn();
        $role = $this->createRole();

        $response = $this->postJson($this->createEndpoint(), [
            'name' => 'John Doe',
            'email' => 'email@example.com',
            'locale' => 'en',
            'access_api' => true,
            'super_admin' => true,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'timezone' => 'Europe/Berlin',
            'roles' => [$role->name],
        ])
            ->assertCreated()
            ->assertJsonCount(1, 'roles')
            ->assertJson([
                'name' => 'John Doe',
                'email' => 'email@example.com',
                'locale' => 'en',
                'access_api' => true,
                'super_admin' => true,
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i',
                'timezone' => 'Europe/Berlin',
                'was_recently_created' => true,
                'guest_email' => 'email@example.com',
                'guest_display_name' => 'John Doe',
                'roles' => [['name' => $role->name]],
            ])->assertJsonMissing(['password'])
            ->assertJsonStructure([
                'id', 'actions', 'authorizations', 'created_at', 'updated_at',
            ]);

        $this->assertTrue(Hash::check('new-password', User::find($response->getData()->id)->password));
    }

    public function test_user_can_update_resource_record()
    {
        $this->seed(PermissionsSeeder::class);
        $user = $this->signIn();
        $originalPasswordHash = $user->password;
        $role = $this->createRole();

        $this->putJson($this->updateEndpoint($user), [
            'name' => 'John Doe',
            'email' => 'email@example.com',
            'locale' => 'en',
            'access_api' => true,
            'super_admin' => true,
            'password' => 'changed-password',
            'password_confirmation' => 'changed-password',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'timezone' => 'Europe/Berlin',
            'roles' => [$role->name],
        ])
            ->assertOk()
            ->assertJsonCount(1, 'roles')
            ->assertJson([
                'name' => 'John Doe',
                'email' => 'email@example.com',
                'locale' => 'en',
                'access_api' => true,
                'super_admin' => true,
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i',
                'timezone' => 'Europe/Berlin',
                'guest_email' => 'email@example.com',
                'guest_display_name' => 'John Doe',
                'roles' => [['name' => $role->name]],
            ])->assertJsonMissing(['password'])
            ->assertJsonStructure([
                'id', 'actions', 'authorizations', 'created_at', 'updated_at',
            ]);

        $user->refresh();

        $this->assertNotSame($originalPasswordHash, $user->password);
        $this->assertTrue(Hash::check('changed-password', $user->password));
    }

    public function test_unauthorized_user_cannot_create_resource_record()
    {
        $this->asRegularUser()->signIn();

        $this->postJson($this->createEndpoint(), array_merge(
            $this->factory()->make()->toArray(),
            ['password' => 'password', 'password_confirmation' => 'password']
        ))->assertForbidden();
    }

    public function test_unauthorized_user_cannot_update_update_resource_record()
    {
        $this->asRegularUser()->signIn();
        $record = $this->createUser();

        $this->putJson($this->updateEndpoint($record), array_merge(
            $this->factory()->make()->toArray(),
        ))->assertForbidden();
    }

    public function test_user_requires_name()
    {
        $user = $this->signIn();

        $this->postJson($this->createEndpoint(), ['name' => ''])->assertJsonValidationErrors(['name']);

        $this->putJson($this->updateEndpoint($user), ['name' => ''])->assertJsonValidationErrors(['name']);
    }

    public function test_user_requires_email()
    {
        $user = $this->signIn();

        $this->postJson($this->createEndpoint(), ['email' => ''])->assertJsonValidationErrors(['email']);
        $this->putJson($this->updateEndpoint($user), ['email' => ''])->assertJsonValidationErrors(['email']);
    }

    public function test_user_requires_valid_email()
    {
        $user = $this->signIn();

        $this->postJson($this->createEndpoint(), ['email' => 'invalid'])->assertJsonValidationErrors(['email']);
        $this->putJson($this->updateEndpoint($user), ['email' => 'invalid'])->assertJsonValidationErrors(['email']);
    }

    public function test_user_requires_unique_email()
    {
        $user = $this->signIn();
        $anotherUser = $this->createUser();

        $this->postJson($this->createEndpoint(), ['email' => $user->email])->assertJsonValidationErrors(['email']);
        $this->putJson($this->updateEndpoint($user), ['email' => $anotherUser->email])->assertJsonValidationErrors(['email']);
    }

    public function test_user_requires_valid_locale()
    {
        $user = $this->signIn();

        $this->postJson($this->createEndpoint(), ['locale' => 'invalid'])->assertJsonValidationErrors(['locale']);
        $this->putJson($this->updateEndpoint($user), ['locale' => 'invalid'])->assertJsonValidationErrors(['locale']);
    }

    public function test_user_requires_valid_timezone()
    {
        $user = $this->signIn();

        $this->postJson($this->createEndpoint(), ['timezone' => 'invalid'])->assertJsonValidationErrors(['timezone']);
        $this->putJson($this->updateEndpoint($user), ['timezone' => 'invalid'])->assertJsonValidationErrors(['timezone']);
    }

    public function test_user_requires_valid_time_format()
    {
        $user = $this->signIn();

        $this->postJson($this->createEndpoint(), ['time_format' => 'invalid'])->assertJsonValidationErrors(['time_format']);
        $this->putJson($this->updateEndpoint($user), ['time_format' => 'invalid'])->assertJsonValidationErrors(['time_format']);
    }

    public function test_user_requires_valid_date_format()
    {
        $user = $this->signIn();

        $this->postJson($this->createEndpoint(), ['date_format' => 'invalid'])->assertJsonValidationErrors(['date_format']);
        $this->putJson($this->updateEndpoint($user), ['date_format' => 'invalid'])->assertJsonValidationErrors(['date_format']);
    }

    public function test_user_requires_password()
    {
        $this->signIn();

        $this->postJson($this->createEndpoint(), ['password' => ''])->assertJsonValidationErrors(['password']);
    }

    public function test_user_doesnt_requires_password_on_update()
    {
        $user = $this->signIn();

        $this->putJson($this->updateEndpoint($user), ['password' => ''])->assertJsonMissingValidationErrors(['password']);
    }

    public function test_user_requires_confirmed_password()
    {
        $user = $this->signIn();

        $this->postJson($this->createEndpoint(), ['password' => 'password'])
            ->assertJsonValidationErrors(['password' => 'The password field confirmation does not match.']);

        $this->putJson($this->updateEndpoint($user), ['password' => 'password'])
            ->assertJsonValidationErrors(['password' => 'The password field confirmation does not match.']);
    }

    public function test_it_can_retrieve_resource_records()
    {
        $this->signIn();

        $this->factory()->count(5)->create();

        $this->getJson($this->indexEndpoint())->assertJsonCount(6, 'data');
    }

    public function test_it_can_retrieve_resource_record()
    {
        $this->signIn();

        $record = $this->factory()->create();

        $this->getJson($this->showEndpoint($record))->assertOk();
    }

    public function test_user_cannot_delete_his_own_account_via_action()
    {
        $user = $this->signIn();

        $this->runAction('user-delete', $user, ['user_id' => $user->getKey()])->assertStatus(409);
        $this->assertDatabaseHas('users', ['id' => $user->getKey()]);
    }

    public function test_it_transfers_data_to_current_user_when_deleting()
    {
        $user = $this->signIn();
        $otherUser = $this->createUser();
        $team = Team::factory()->create(['user_id' => $otherUser->id]);

        $this->deleteJson('/api/users/'.$otherUser->getKey())->assertNoContent();
        $team->refresh();
        $this->assertTrue($user->managesTeam($team));
        $this->assertDatabaseMissing('users', ['id' => $otherUser->getKey()]);
    }

    public function test_a_transfer_data_to_user_id_can_be_provided()
    {
        $user = $this->signIn();
        $otherUser = $this->createUser();

        $this->deleteJson('/api/users/'.$otherUser->getKey().'?transfer_data_to='.$user->getKey())->assertNoContent();
        $this->assertDatabaseMissing('users', ['id' => $otherUser->getKey()]);
    }

    public function test_user_cannot_delete_his_own_account_via_api()
    {
        $user = $this->signIn();

        $this->deleteJson('/api/users/'.$user->getKey())->assertStatus(409);
        $this->assertDatabaseHas('users', ['id' => $user->getKey()]);
    }

    public function test_user_defaults_are_associated_after_user_creation()
    {
        DatabaseState::seed();

        $user = $this->createUser();

        $this->assertCount(1, $user->dashboards);
        $this->assertTrue($user->dashboards->first()->is_default);

        $this->assertNotNull(Filter::hasDefaultFor('deals', 'deals', $user->id)->first());
        $this->assertNotNull(Filter::hasDefaultFor('deals', Board::FILTERS_VIEW, $user->id)->first());
        $this->assertNotNull(Filter::hasDefaultFor('activities', 'activities', $user->id)->first());
    }
}
