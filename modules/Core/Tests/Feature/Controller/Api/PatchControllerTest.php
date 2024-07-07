<?php
 

namespace Modules\Core\Tests\Feature\Controller\Api;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\App;
use Modules\Core\App\Updater\Patcher;
use Modules\Core\Tests\Feature\Updater\TestsUpdater;
use Tests\TestCase;

/**
 * @group updater
 */
class PatchControllerTest extends TestCase
{
    use TestsUpdater;

    public function test_unauthenticated_user_cannot_access_patches_endpoints()
    {
        $this->getJson('api/patches')->assertUnauthorized();
        $this->getJson('api/patches/FAKE_KEY')->assertUnauthorized();
    }

    public function test_unauthorized_user_cannot_access_patches_endpoints()
    {
        $this->asRegularUser()->signIn();

        $this->getJson('api/patches')->assertForbidden();
        $this->postJson('api/patches/FAKE_KEY')->assertForbidden();
    }

    public function test_user_can_retrieve_available_patches()
    {
        $this->signIn();

        App::singleton(Patcher::class, function () {
            return $this->createPatcherInstance([
                new Response(200, [], $this->patcherResponse()),
            ]);
        });

        $this->getJson('/api/patches')->assertJsonCount(2);
    }

    public function test_user_cannot_apply_patch_that_is_already_applied()
    {
        $this->signIn();

        $response = json_encode([
            [
                'date' => '2021-08-24T18:52:54.000000Z',
                'description' => 'Fixes issue with activities',
                'token' => '96671235-ddb3-40ab-8ab9-3ca5df8de6b7',
                'version' => '1.0.0',
            ],
        ]);

        App::singleton(Patcher::class, function () use ($response) {
            return $this->createPatcherInstance([
                new Response(200, [], $response),
                new Response(200, [], $response),
            ]);
        });

        $patcher = app(Patcher::class);
        $patch = $patcher->find('96671235-ddb3-40ab-8ab9-3ca5df8de6b7');
        $patch->markAsApplied();

        $this->postJson("/api/patches/{$patch->token()}")->assertStatus(409);
    }
}
