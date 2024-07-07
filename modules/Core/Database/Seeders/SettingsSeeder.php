<?php
 

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\App\Settings\DefaultSettings;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        settings()->flush();

        $defaultSettings = array_merge(DefaultSettings::get(), ['_seeded' => true]);

        foreach ($defaultSettings as $name => $value) {
            settings()->set([$name => $value]);
        }

        settings()->save();
    }
}
