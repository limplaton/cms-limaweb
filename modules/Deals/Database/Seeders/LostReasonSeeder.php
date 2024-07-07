<?php
 

namespace Modules\Deals\Database\Seeders;

use Illuminate\Database\Seeder;

class LostReasonSeeder extends Seeder
{
    /**
     * @var array
     */
    public $reasons = [
        'Client went silent',
        'Not responsive',
        'Doesn\'t pick up the phone, doesn\'t respond',
        'They couldn\'t afford our services',
        'Didn\'t have the budget',
        'Went with our competitor X',
        'Lack of expertise',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->reasons as $reason) {
            \Modules\Deals\App\Models\LostReason::create(['name' => $reason]);
        }
    }
}
