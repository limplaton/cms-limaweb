<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\App\Environment;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Fields\CustomFieldFileCache;
use Modules\Core\Database\State\DatabaseState;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Innoclapps::clearCache();
        Innoclapps::muteAllCommunicationChannels();
        CustomFieldFileCache::flush();

        settings(['_seeded' => false]);

        DatabaseState::seed();

        $this->call(DemoDataSeeder::class);

        Environment::setInstallationDate();
    }
}
