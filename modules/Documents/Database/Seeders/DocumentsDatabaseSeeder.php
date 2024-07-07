<?php
 

namespace Modules\Documents\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Documents\Database\State\EnsureDocumentTypesArePresent;

class DocumentsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        call_user_func(new EnsureDocumentTypesArePresent);
    }
}
