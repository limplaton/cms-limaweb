<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Testing\RefreshDatabase as BaseRefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Str;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

trait RefreshDatabase
{
    use BaseRefreshDatabase;

    /**
     * The checksum cached in the migrationChecksum.txt file.
     */
    public static ?string $dbCachedChecksum = null;

    /**
     * The current checksum calculated by the application.
     */
    public static ?string $dbCurrentChecksum = null;

    /**
     * Refresh a conventional test database.
     *
     * @throws \JsonException
     */
    protected function refreshTestDatabase()
    {
        if (! RefreshDatabaseState::$migrated) {
            $cachedChecksum = static::$dbCachedChecksum ??= $this->getCachedMigrationChecksum();
            $currentChecksum = static::$dbCurrentChecksum ??= $this->calculateMigrationChecksum();

            if ($cachedChecksum !== $currentChecksum) {
                $this->artisan('migrate:fresh', $this->migrateFreshUsing());

                $this->app[Kernel::class]->setArtisan(null);

                $this->storeMigrationChecksum($currentChecksum);
            }

            RefreshDatabaseState::$migrated = true;
        }

        $this->beginDatabaseTransaction();
    }

    /**
     * Perform any work that should take place before the database has started refreshing.
     *
     * @return void
     */
    protected function beforeRefreshingDatabase()
    {
        foreach ($this->getCustomMigrationPaths() as $path) {
            $this->app['migrator']->path($path);
        }
    }

    /**
     * Calculate a checksum based on the migrations name and last modified date.
     *
     * @throws \JsonException
     */
    protected function calculateMigrationChecksum(): string
    {
        // Filter out non-existing paths
        $paths = collect($this->getMigrationPaths())
            ->map(fn ($path) => realpath($path))
            ->filter()
            ->toArray();

        $finder = Finder::create()
            ->in($paths)
            ->name('*.php')
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
            ->files();

        // Get all the migration files and their last modified date
        $migrations = collect(iterator_to_array($finder))
            ->map(fn (SplFileInfo $fileInfo) => [$fileInfo->getMTime()])
            // Reset the array keys so there is less data
            ->values()
            ->toArray();

        // Add the current git branch
        $checkBranch = new Process(['git', 'branch', '--show-current']);
        $checkBranch->run();

        $migrations['gitBranch'] = trim($checkBranch->getOutput());

        // Create a hash
        return hash('sha256', json_encode($migrations, JSON_THROW_ON_ERROR));
    }

    /**
     * Get the cached migration checksum.
     */
    protected function getCachedMigrationChecksum(): ?string
    {
        return rescue(fn () => file_get_contents($this->getMigrationChecksumFile()), null, false);
    }

    /**
     * Store the migration checksum.
     */
    protected function storeMigrationChecksum(string $checksum): void
    {
        file_put_contents($this->getMigrationChecksumFile(), $checksum);
    }

    /**
     * The paths that should be used to discover migrations.
     *
     * @return array<string>
     */
    protected function getMigrationPaths(): array
    {
        $paths = [
            database_path('migrations'),
            ...app('migrator')->paths(),
            ...$this->getCustomMigrationPaths(),
        ];

        return array_unique($paths);
    }

    /**
     * Custom migration paths that should be used when discovering migrations.
     *
     * @return array<string>
     */
    protected function getCustomMigrationPaths(): array
    {
        return [base_path('tests/Migrations')];
    }

    /**
     * Provides a configurable migration checksum file path.
     */
    protected function getMigrationChecksumFile(): string
    {
        $connection = $this->app[ConnectionInterface::class];

        $databaseNameSlug = Str::slug($connection->getDatabaseName());

        return storage_path("framework/testing/migration-checksum_{$databaseNameSlug}.txt");
    }
}
