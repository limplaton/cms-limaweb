<?php
 

namespace Modules\Core\Tests\Feature\Updater;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Modules\Core\App\Updater\Patcher;
use Modules\Core\App\Updater\Updater;

trait TestsUpdater
{
    protected $guzzleMock;

    protected function createUpdaterInstance($responses, $config = [])
    {
        return new Updater($this->createClientInstance($responses), new Filesystem, array_merge([
            'purchase_key' => config('updater.purchase_key'),
            'archive_url' => config('updater.archive_url'),
            'download_path' => config('updater.download_path'),
            'version_installed' => '1.0.0',
            'exclude_folders' => config('updater.exclude_folders'),
            'exclude_files' => config('updater.exclude_files'),
            'permissions' => config('updater.permissions'),
        ], $config));
    }

    protected function createPatcherInstance($responses, $config = [])
    {
        return new Patcher($this->createClientInstance($responses), new Filesystem, array_merge([
            'purchase_key' => config('updater.purchase_key'),
            'patches_url' => config('updater.patches_archive_url'),
            'download_path' => config('updater.download_path'),
            'version_installed' => '1.0.0',
            'permissions' => config('updater.permissions'),
        ], $config));
    }

    protected function archiveResponse()
    {
        $url = config('updater.archive_url');

        return $url.'/v1.0.0.zip'.$url.'/v1.1.0.zip';
    }

    protected function patcherResponse()
    {
        return json_encode([
            [
                'date' => '2021-08-24T18:52:54.000000Z',
                'description' => 'Fixes issue with activities',
                'token' => '96671235-ddb3-40ab-8ab9-3ca5df8de6b7',
                'version' => '1.0.0',
            ],
            [
                'date' => '2021-08-24T18:54:23.000000Z',
                'description' => 'Fixes issue with calendar',
                'token' => 'f7595877-f826-4f4c-b6e2-60722fc4a30d',
                'version' => '1.0.0',
            ],
        ]);
    }

    protected function createClientInstance($responses)
    {
        $mock = new MockHandler(Arr::wrap($responses));

        $handlerStack = HandlerStack::create($mock);

        $this->guzzleMock = $mock;

        return new Client(['handler' => $handlerStack]);
    }

    protected function tearDown(): void
    {
        Patcher::providePermissionsCheckerFinderUsing(null);
        Patcher::checkPermissionsUsing(null);
        Updater::providePermissionsCheckerFinderUsing(null);
        Updater::checkPermissionsUsing(null);
        $this->guzzleMock = null;
        $this->cleanFixturesFiles();

        parent::tearDown();
    }

    protected function cleanFixturesFiles()
    {
        foreach ([
            config_path('test-config.php'),
            app_path('TestModel.php'),
            app_path('UpdateNewFeature/DummyClass.php'),
            base_path('excluded/file.php'),
            base_path('invalid-file.php'),
            base_path('test-root-file.php'),
            base_path('routes/test-routes.php'),
            base_path('database/migrations/2010_10_12_000000_create_test_update_table.php'),
        ] as $file) {
            if (file_exists($file)) {
                chmod($file, 0755);
                unlink($file);
            }
        }

        foreach ([
            app_path('UpdateNewFeature'),
            base_path('excluded'),

        ] as $dir) {
            if (is_dir($dir)) {
                File::cleanDirectory($dir);
                rmdir($dir);
            }
        }

        foreach ([
            storage_path('updater/96671235-ddb3-40ab-8ab9-3ca5df8de6b7'),
            storage_path('updater/96671235-ddb3-40ab-8ab9-3ca5df8de6b7'),
        ] as $patchDir) {
            if (is_dir($patchDir)) {
                File::cleanDirectory($patchDir);

                if (is_dir($patchConfigDir = $patchDir.'/config')) {
                    rmdir($patchConfigDir);
                }

                if (is_dir($patchRoutesDir = $patchDir.'/routes')) {
                    rmdir($patchRoutesDir);
                }

                rmdir($patchDir);
            }
        }

        app(Updater::class)->clearTemporaryPath();
    }

    protected function createZipFromFixtureFiles($path = null)
    {
        // Get real path for our folder
        $rootPath = $this->fixtureFilesPath();
        $path ??= $this->zipPathForFixtureFiles();
        // Initialize archive object
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        // Create recursive directory iterator
        /** @var SplFileInfo[] $files */
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($rootPath),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            // Skip directories (they would be added automatically)
            if (! $file->isDir()) {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);

                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
            }
        }

        // Zip archive will be created only after closing object
        $zip->close();

        return $path;
    }
}
