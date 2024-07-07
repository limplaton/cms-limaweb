<?php
 

namespace Modules\Users\Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Modules\Users\App\Mail\InvitationCreated;
use Modules\Users\App\Models\Team;
use Modules\Users\App\Models\UserInvitation;
use Tests\TestCase;

class UserInvitationControllerTest extends TestCase
{
    public function test_unauthenticated_user_cannot_access_invitation_endpoints()
    {
        $this->postJson('/api/users/invite')->assertUnauthorized();
    }

    public function test_invitation_cannot_be_sent_by_non_authorized_user()
    {
        $this->asRegularUser()->signIn();

        $this->postJson('/api/users/invite')->assertForbidden();
    }

    public function test_invitation_can_be_sent_by_authorized_user()
    {
        $this->signIn();

        Mail::fake();

        $role = $this->createRole();
        $team = Team::factory()->create();

        $this->postJson('/api/users/invite', [
            'emails' => [$email = 'user@example.com'],
            'super_admin' => 0,
            'access_api' => 1,
            'roles' => $roles = [$role->name],
            'teams' => $teams = [$team->id],
        ]);

        Mail::assertQueued(InvitationCreated::class, 1);

        $this->assertDatabaseHas('user_invitations', [
            'email' => $email,
            'super_admin' => 0,
            'access_api' => 1,
            'roles' => json_encode($roles),
            'teams' => json_encode($teams),
        ]);
    }

    public function test_invitation_requires_email()
    {
        $this->signIn();

        $this->postJson('/api/users/invite', [
            'emails' => null,
        ])->assertJsonValidationErrors('emails');

        $this->postJson('/api/users/invite', [
            'emails' => '',
        ])->assertJsonValidationErrors('emails');

        $this->postJson('/api/users/invite', [
            'emails' => [],
        ])->assertJsonValidationErrors('emails');

        $this->postJson('/api/users/invite', [
            'emails' => ['dummy'],
        ])->assertJsonValidationErrors('emails.0'); // invalid email
    }

    public function test_cannot_invite_user_that_already_exist()
    {
        $user = $this->signIn();

        $this->postJson('/api/users/invite', [
            'emails' => [$user->email],
        ])->assertJsonValidationErrors(['emails.0' => __('validation.unique', ['attribute' => 'E-Mail Address'])]);
    }

    public function test_multiple_users_can_be_invited()
    {
        $this->signIn();

        $this->postJson('/api/users/invite', [
            'emails' => ['email1@example.com', 'email2@example.com'],
        ]);

        $this->assertDatabaseHas('user_invitations', [
            'email' => 'email1@example.com',
        ]);

        $this->assertDatabaseHas('user_invitations', [
            'email' => 'email2@example.com',
        ]);
    }

    public function test_previous_invitation_is_deleted_when_inviting_the_same_user()
    {
        $this->signIn();

        $invitation = UserInvitation::factory()->create(['email' => 'user@example.com']);

        $this->postJson('/api/users/invite', [
            'emails' => ['user@example.com'],
        ]);

        $this->assertDatabaseMissing('user_invitations', [
            $invitation->getKeyName() => $invitation->id,
        ]);
    }
}
