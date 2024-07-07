<?php
 

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Database\State\EnsureCountriesArePresent;

class CountriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        call_user_func(new EnsureCountriesArePresent);
    }
}
