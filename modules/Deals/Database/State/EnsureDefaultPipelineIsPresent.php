<?php
 

namespace Modules\Deals\Database\State;

use Illuminate\Support\Facades\DB;

class EnsureDefaultPipelineIsPresent
{
    public array $stages = [
        [
            'name' => 'Qualified To Buy',
            'win_probability' => 100,
            'display_order' => 1,
        ],
        [
            'name' => 'Contact Made',
            'win_probability' => 100,
            'display_order' => 2,
        ],
        [
            'name' => 'Presentation Scheduled',
            'win_probability' => 100,
            'display_order' => 3,
        ],
        [
            'name' => 'Proposal Made',
            'win_probability' => 100,
            'display_order' => 4,
        ],
        [
            'name' => 'Appointment Scheduled',
            'win_probability' => 100,
            'display_order' => 5,
        ],
    ];

    public function __invoke(): void
    {
        if ($this->present()) {
            return;
        }

        $pipeline = \Modules\Deals\App\Models\Pipeline::create([
            'name' => 'Sales Pipeline',
            'flag' => 'default',
        ]);

        $pipeline->stages()->createMany($this->stages);
    }

    private function present(): bool
    {
        return DB::table('pipelines')->where('flag', 'default')->count() > 0;
    }
}
