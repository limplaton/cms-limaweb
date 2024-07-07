<?php
 

namespace Modules\Core\Tests\Feature\Updater;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\App;
use Modules\Core\App\Updater\Patcher;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * @group updater
 */
class PatchCommandTest extends TestCase
{
    use TestsUpdater;

    public function test_patches_are_applied()
    {
        $path = $this->createZipFromFixtureFiles();

        App::singleton(Patcher::class, function () use ($path) {
            return $this->createPatcherInstance([
                new Response(200, [], $this->patcherResponse()),
                new Response(200, [], file_get_contents($path)),
                new Response(200, [], file_get_contents($path)),
            ]);
        });

        $patcher = app(Patcher::class);
        $patches = $patcher->getAvailablePatches();

        $this->artisan('updater:patch')
            ->expectsOutput('Configuring purchase key.')
            ->expectsOutput('Putting the application into maintenance mode.')
            ->expectsOutput('Applying patch with token: '.$patches[0]->token())
            ->expectsOutput('Applying patch with token: '.$patches[1]->token())
            ->expectsOutput('Bringing the application out of maintenance mode.')
            ->assertSuccessful();

        $this->assertFileExists(config_path('test-config.php'));
        $this->assertFileExists(base_path('test-root-file.php'));
        $this->assertFileExists(base_path('routes/test-routes.php'));
    }

    public function test_it_shows_message_that_no_patches_are_available()
    {
        App::singleton(Patcher::class, function () {
            return $this->createPatcherInstance([
                new Response(200, [], json_encode([])),
            ]);
        });

        $this->artisan('updater:patch')
            ->expectsOutput('Configuring purchase key.')
            ->expectsOutput('No patches available for the current installation version.')
            ->assertSuccessful();

        $this->assertFileDoesNotExist(config_path('test-config.php'));
        $this->assertFileDoesNotExist(base_path('test-root-file.php'));
        $this->assertFileDoesNotExist(base_path('routes/test-routes.php'));
    }

    public function test_it_does_not_perform_patching_if_user_was_recently_active_and_force_is_not_true()
    {
        $this->createUser(['last_active_at' => now()]);

        $path = $this->createZipFromFixtureFiles();

        App::singleton(Patcher::class, function () use ($path) {
            return $this->createPatcherInstance([
                new Response(200, [], $this->patcherResponse()),
                new Response(200, [], file_get_contents($path)),
            ]);
        });

        $this->artisan('updater:patch', ['--force' => false])
            ->expectsOutput('Skipping patching, the last active user was in less than 30 minutes, try later.');
    }

    public function test_it_does_not_perform_patching_with_already_applied_patches()
    {
        $path = $this->createZipFromFixtureFiles();

        App::singleton(Patcher::class, function () use ($path) {
            return $this->createPatcherInstance([
                new Response(200, [], $this->patcherResponse()),
                new Response(200, [], file_get_contents($path)),
            ]);
        });

        $patcher = app(Patcher::class);
        $patches = $patcher->getAvailablePatches();
        $patches[0]->markAsApplied();

        $this->artisan('updater:patch', ['--force' => false])
            ->doesntExpectOutput('Applying patch with token: '.$patches[0]->token())
            ->expectsOutput('Applying patch with token: '.$patches[1]->token());
    }

    protected function zipPathForFixtureFiles()
    {
        return storage_path('updater/patch.zip');
    }

    protected function fixtureFilesPath()
    {
        return module_path('Core', 'Tests/Fixtures/patch');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Provide fake Finder to the actual fixtures to avoid looping over thousands of files
        // to check whether they have the necessary permissions
        Patcher::providePermissionsCheckerFinderUsing(function () {
            return (new Finder)->in(module_path('Core', 'Tests/Fixtures/patch'));
        });

        $this->cleanFixturesFiles();
    }
}
