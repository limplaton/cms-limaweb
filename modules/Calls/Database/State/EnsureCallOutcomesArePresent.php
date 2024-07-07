<?php
 

namespace Modules\Calls\Database\State;

use Illuminate\Support\Facades\DB;

class EnsureCallOutcomesArePresent
{
    public array $outcomes = [
        'No Answer' => '#f43f5e',
        'Busy' => '#f43f5e',
        'Wrong Number' => '#8898aa',
        'Unavailable' => '#8898aa',
        'Left voice message' => '#ffd600',
        'Moved conversation forward' => '#a3e635',
    ];

    public function __invoke(): void
    {
        if ($this->present()) {
            return;
        }

        foreach ($this->outcomes as $name => $color) {
            \Modules\Calls\App\Models\CallOutcome::create(['name' => $name, 'swatch_color' => $color]);
        }
    }

    private function present(): bool
    {
        return DB::table('call_outcomes')->count() > 0;
    }
}
