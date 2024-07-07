<?php
 

namespace Modules\Core\Tests\Feature\Controller\Api;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\App;
use Mockery\MockInterface;
use Modules\Core\App\Database\Migrator;
use Modules\Core\App\Updater\Updater;
use Modules\Core\Tests\Feature\Updater\TestsUpdater;
use Tests\TestCase;

/**
 * @group updater
 */
class UpdateControllerTest extends TestCase
{
    use TestsUpdater;

    public function test_unauthenticated_user_cannot_access_update_endpoints()
    {
        $this->getJson('api/update')->assertUnauthorized();
        $this->getJson('ap/update/FAKE_KEY')->assertUnauthorized();
    }

    public function test_unauthorized_user_cannot_access_update_endpoints()
    {
        $this->asRegularUser()->signIn();

        $this->getJson('api/update')->assertForbidden();
        $this->postJson('api/update/FAKE_KEY')->assertForbidden();
    }

    public function test_update_information_can_be_retrieved()
    {
        $this->signIn();

        App::singleton(Updater::class, function () {
            return $this->createUpdaterInstance([
                new Response(200, [], $this->archiveResponse()),
            ], ['version_installed' => '1.1.0']);
        });

        $this->getJson('/api/update')->assertExactJson([
            'installed_version' => '1.1.0',
            'is_new_version_available' => false,
            'latest_available_version' => '1.1.0',
            'purchase_key' => config('updater.purchase_key'),
        ]);
    }

    public function test_user_can_perform_update()
    {
        // Updater runs migration in "handlePostUpdateActions" method, which breaks
        // the transactions in tests, we need to make sure the migration are not executed.
        $this->app->bind(Migrator::class, function () {
            return $this->partialMock(Migrator::class, function (MockInterface $mock) {
                $mock->shouldReceive('run');
            });
        });

        $this->signIn();

        App::singleton(Updater::class, function () {
            return $this->createUpdaterInstance([
                new Response(200, [], $this->archiveResponse()),
                new Response(200, [], file_get_contents($this->createZipFromFixtureFiles())),
            ]);
        });

        $this->postJson('/api/update')->assertNoContent();
    }

    protected function fixtureFilesPath()
    {
        return module_path('Core', 'Tests/Fixtures/update');
    }

    protected function zipPathForFixtureFiles()
    {
        return storage_path('updater/test-1.1.0.zip');
    }
}
