<?php
 

namespace Modules\Users\Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    public function test_unauthenticated_user_cannot_access_profile_endpoints()
    {
        $this->postJson('api/profile')->assertStatus(401);
    }

    public function test_logged_in_user_can_update_his_profile()
    {
        $this->signIn();

        $this->putJson('/api/profile', $data = [
            'name' => '--changed-name--',
            'email' => 'changed-email@example.com',
            'timezone' => collect(tz()->all())->random(),
            'time_format' => collect(config('core.time_formats'))->random(),
            'locale' => 'en',
            'date_format' => collect(config('core.date_formats'))->random(),
        ])->assertOk()
            ->assertJson($data);
    }

    public function test_profile_update_requires_email()
    {
        $this->signIn();

        $this->putJson('/api/profile', [
            'email' => '',
        ])->assertJsonValidationErrorFor('email');
    }

    public function test_profile_update_requires_valid_email()
    {
        $user = $this->signIn();

        $this->putJson('/api/profile', [
            'email' => '-invalid-',
        ])->assertJsonValidationErrorFor('email');

        $this->putJson('/api/profile', [
            'email' => $user->email,
        ])->assertJsonMissingValidationErrors('email');
    }

    public function test_profile_update_requires_name()
    {
        $this->signIn();

        $this->putJson('/api/profile', [
            'name' => '',
        ])->assertJsonValidationErrorFor('name');
    }

    public function test_profile_update_requires_time_format()
    {
        $this->signIn();

        $this->putJson('/api/profile', [
            'time_format' => '',
        ])->assertJsonValidationErrorFor('time_format');
    }

    public function test_profile_update_requires_valid_time_format()
    {
        $this->signIn();

        $this->putJson('/api/profile', [
            'time_format' => '-invalid-',
        ])->assertJsonValidationErrorFor('time_format');

        $this->putJson('/api/profile', [
            'time_format' => collect(config('core.time_formats'))->random(),
        ])->assertJsonMissingValidationErrors('time_format');
    }

    public function test_profile_update_requires_date_format()
    {
        $this->signIn();

        $this->putJson('/api/profile', [
            'date_format' => '',
        ])->assertJsonValidationErrorFor('date_format');
    }

    public function test_profile_update_requires_valid_date_format()
    {
        $this->signIn();

        $this->putJson('/api/profile', [
            'date_format' => '-invalid-',
        ])->assertJsonValidationErrorFor('date_format');

        $this->putJson('/api/profile', [
            'date_format' => collect(config('core.date_formats'))->random(),
        ])->assertJsonMissingValidationErrors('date_format');
    }

    public function test_profile_update_requires_locale()
    {
        $this->signIn();

        $this->putJson('/api/profile', [
            'locale' => '',
        ])->assertJsonValidationErrorFor('locale');
    }

    public function test_profile_update_requires_valid_locale()
    {
        $this->signIn();

        $this->putJson('/api/profile', [
            'locale' => 'does-not-exists',
        ])->assertJsonValidationErrorFor('locale');

        $this->putJson('/api/profile', [
            'locale' => 'en',
        ])->assertJsonMissingValidationErrors('locale');
    }

    public function test_profile_update_requires_timezone()
    {
        $this->signIn();

        $this->putJson('/api/profile', [
            'timezone' => '',
        ])->assertJsonValidationErrorFor('timezone');
    }

    public function test_profile_update_requires_valid_timezone()
    {
        $this->signIn();

        $this->putJson('/api/profile', [
            'timezone' => 'invalid',
        ])->assertJsonValidationErrorFor('timezone');

        $this->putJson('/api/profile', [
            'timezone' => 'Europe/Berlin',
        ])->assertJsonMissingValidationErrors('timezone');
    }

    public function test_logged_in_user_can_change_password_via_profile()
    {
        $old_password = 'old-password';
        $new_password = 'new-password';

        $user = $this->withUserAttrs([
            'password' => bcrypt($old_password),
        ])->createUser();

        $this->signIn($user);

        $this->putJson('/api/profile/password', [
            'old_password' => $old_password,
            'password' => $new_password,
            'password_confirmation' => $new_password,
        ]);

        $this->assertTrue(
            Hash::check($new_password, $user->fresh()->password)
        );
    }

    public function test_user_must_provide_old_password_when_changing_password_via_profile()
    {
        $old_password = 'old-password';
        $new_password = 'new-password';

        $user = $this->withUserAttrs([
            'password' => bcrypt($old_password),
        ])->createUser();

        $this->signIn($user);

        $this->putJson('/api/profile/password', [
            'old_password' => '',
            'password' => $new_password,
            'password_confirmation' => $new_password,
        ])->assertJsonValidationErrors(['old_password']);
    }

    public function test_user_old_password_must_match_when_changing_via_profile()
    {
        $old_password = 'old-password';
        $new_password = 'new-password';

        $user = $this->withUserAttrs([
            'password' => bcrypt($old_password),
        ])->createUser();

        $this->signIn($user);

        $this->putJson('/api/profile/password', [
            'old_password' => 'incorrect-old-password',
            'password' => $new_password,
            'password_confirmation' => $new_password,
        ])->assertJsonValidationErrors(['old_password']);
    }

    public function test_user_new_password_must_match_when_changing_via_profile()
    {
        $old_password = 'old-password';
        $new_password = 'new-password';

        $user = $this->withUserAttrs([
            'password' => bcrypt($old_password),
        ])->createUser();

        $this->signIn($user);

        $this->putJson('/api/profile/password', [
            'old_password' => $old_password,
            'password' => $new_password,
            'password_confirmation' => 'not-matching-with-password',
        ])->assertJsonValidationErrors(['password']);
    }

    public function test_user_email_address_must_be_unique_when_updating_via_profile()
    {
        $this->signIn();

        $user = $this->createUser();

        $this->putJson('/api/profile', ['email' => $user->email])
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_retrieve_his_profile()
    {
        $user = $this->signIn();

        $this->getJson('api/me')->assertOk()->assertJson(['id' => $user->id]);
    }
}
