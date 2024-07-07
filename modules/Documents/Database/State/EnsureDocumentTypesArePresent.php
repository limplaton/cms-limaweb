<?php
 

namespace Modules\Documents\Database\State;

use Illuminate\Support\Facades\DB;

class EnsureDocumentTypesArePresent
{
    public array $types = [
        'Proposal' => '#a3e635',
        'Quote' => '#64748b',
        'Contract' => '#ffd600',
    ];

    public function __invoke(): void
    {
        if ($this->present()) {
            return;
        }

        foreach ($this->types as $name => $color) {
            $model = \Modules\Documents\App\Models\DocumentType::create([
                'name' => $name,
                'swatch_color' => $color,
                'flag' => strtolower($name),
            ]);

            if ($model->flag === 'proposal') {
                $model::setDefault($model->getKey());
            }
        }
    }

    private function present(): bool
    {
        return DB::table('document_types')->count() > 0;
    }
}
