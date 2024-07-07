<?php
 

namespace Modules\Users\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserAvatarControllerTest extends TestCase
{
    public function test_unauthenticated_user_cannot_add_avatars()
    {
        $this->postJson('api/users/FAKE_ID/avatar')->assertStatus(401);
    }

    public function test_valid_avatar_must_be_provided()
    {
        $user = $this->signIn();

        $this->postJson('api/users/'.$user->id.'/avatar', [
            'avatar' => 'not-valid-image',
        ])->assertStatus(422);
    }

    public function test_user_can_add_avatar_to_their_profile()
    {
        $user = $this->signIn();

        Storage::fake('public');

        $this->postJson('api/users/'.$user->id.'/avatar', [
            'avatar' => $file = UploadedFile::fake()->image('avatar.jpg'),
        ]);

        // Re-query the user
        $user = $user->fresh();

        $this->assertEquals('avatars/'.$file->hashName(), $user->avatar);

        Storage::disk('public')->assertExists('avatars/'.$file->hashName());
    }

    public function test_user_can_delete_the_avatar()
    {
        $user = $this->signIn();

        Storage::fake('public');

        $this->postJson('api/users/'.$user->id.'/avatar', [
            'avatar' => $file = UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $this->deleteJson('api/users/'.$user->id.'/avatar')
            ->assertJson(['avatar' => null, 'uploaded_avatar_url' => null]);

        // Storage::disk('public')->assertMissing('avatars/' . $file->hashName());
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('framework/testing/disks'));
        parent::tearDown();
    }
}
