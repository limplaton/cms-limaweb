<?php
 

namespace Modules\Core\Tests\Feature\Updater;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Modules\Core\App\Updater\Updater;
use Tests\TestCase;

/**
 * @group updater
 */
class UpdateCommandTest extends TestCase
{
    use TestsUpdater;

    public function test_can_perform_update_via_the_console_command()
    {
        App::singleton(Updater::class, function () {
            return $this->createUpdaterInstance([
                new Response(200, [], $this->archiveResponse()),
                new Response(200, [], file_get_contents($this->createZipFromFixtureFiles())),
            ]);
        });

        $this->artisan('updater:update')
            ->expectsOutput('Configuring purchase key.')
            ->expectsOutput('Preparing update.')
            ->expectsOutput('Putting the application into maintenance mode.')
            ->expectsOutput('Performing update, this may take a while.')
            ->expectsOutput('Bringing the application out of maintenance mode.')
            ->doesntExpectOutput('Increasing PHP config values.')
            ->doesntExpectOutput('Optimizing application.')
            ->assertSuccessful();

        $this->assertTrue(Schema::hasTable('test_update'));
    }

    public function test_it_does_not_perform_update_if_user_was_recently_active_and_force_is_not_true()
    {
        $this->createUser(['last_active_at' => now()]);

        App::singleton(Updater::class, function () {
            return $this->createUpdaterInstance([
                new Response(200, [], $this->archiveResponse()),
                new Response(200, [], file_get_contents($this->createZipFromFixtureFiles())),
            ]);
        });

        $this->artisan('updater:update', ['--force' => false])
            ->expectsOutput('Skipping update, the last active user was in less than 30 minutes, try later.');

    }

    public function test_update_command_uses_the_provided_purchase_key()
    {
        App::singleton(Updater::class, function () {
            return $this->createUpdaterInstance([
                new Response(200, [], $this->archiveResponse()),
                new Response(200, [], file_get_contents($this->createZipFromFixtureFiles())),
            ]);
        });

        $this->artisan('updater:update', [
            '--key' => 'dummy',
        ]);

        $updater = app(Updater::class);

        $this->assertEquals('dummy', $updater->getPurchaseKey());
    }

    public function test_update_command_uses_the_configuration_purchase_key_when_provided_key_is_empty_string()
    {
        App::singleton(Updater::class, function () {
            return $this->createUpdaterInstance([
                new Response(200, [], $this->archiveResponse()),
                new Response(200, [], file_get_contents($this->createZipFromFixtureFiles())),
            ], ['purchase_key' => 'user-purchase-key']);
        });

        $this->artisan('updater:update', [
            '--key' => '',
        ]);
        $updater = app(Updater::class);

        $this->assertEquals('user-purchase-key', $updater->getPurchaseKey());
    }

    public function test_update_command_uses_the_configuration_purchase_key_when_provided_key_is_empty_null()
    {
        App::singleton(Updater::class, function () {
            return $this->createUpdaterInstance([
                new Response(200, [], $this->archiveResponse()),
                new Response(200, [], file_get_contents($this->createZipFromFixtureFiles())),
            ], ['purchase_key' => 'user-purchase-key']);
        });

        $this->artisan('updater:update', [
            '--key' => null,
        ]);
        $updater = app(Updater::class);

        $this->assertEquals('user-purchase-key', $updater->getPurchaseKey());
    }

    public function test_it_does_not_perform_update_if_the_latest_is_already_installed()
    {
        $updater = $this->createUpdaterInstance([
            new Response(200, [], $this->archiveResponse()),
            new Response(200, [], file_get_contents($this->createZipFromFixtureFiles())),
        ], ['version_installed' => '1.1.0']);

        App::singleton(Updater::class, function () use (&$updater) {
            return $updater;
        });

        $this->artisan('updater:update')
            ->expectsOutput('The latest version '.$updater->getVersionInstalled().' is already installed.')
            ->assertSuccessful();
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
