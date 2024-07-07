<?php
 

namespace Modules\Activities\Database\State;

use Illuminate\Support\Facades\DB;

class EnsureActivityTypesArePresent
{
    public array $types = [
        'Call' => ['#a3e635', 'Phone'],
        'Meeting' => ['#64748b', 'Users'],
        'Task' => ['#ffd600', 'CheckCircle'],
        'Email' => ['#818cf8', 'Mail'],
        'Deadline' => ['#f43f5e', 'Clock'],
    ];

    public function __invoke(): void
    {
        if ($this->present()) {
            return;
        }

        foreach ($this->types as $name => $options) {
            $model = \Modules\Activities\App\Models\ActivityType::create([
                'name' => $name,
                'swatch_color' => $options[0],
                'icon' => $options[1],
                'flag' => strtolower($name),
            ]);

            if ($model->flag === 'task') {
                $model::setDefault($model->getKey());
            }
        }
    }

    private function present(): bool
    {
        return DB::table('activity_types')->count() > 0;
    }
}
