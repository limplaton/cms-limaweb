<?php
 

use Modules\Core\App\Updater\UpdatePatcher;
use Modules\Documents\App\Models\DocumentType;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        if ($this->missingDocumentTypes()) {
            $this->createDefaultDocumentTypes();
        }
    }

    public function shouldRun(): bool
    {
        return $this->missingDocumentTypes();
    }

    protected function createDefaultDocumentTypes(): void
    {
        foreach ([
            'Proposal' => '#a3e635',
            'Quote' => '#64748b',
            'Contract' => '#ffd600',
        ] as $name => $color) {
            $model = new DocumentType;
            $model->forceFill([
                'name' => $name,
                'swatch_color' => $color,
                'flag' => strtolower($name),
            ])->save();

            if ($model->flag == 'proposal') {
                DocumentType::setDefault($model->getKey());
            }
        }
    }

    protected function missingDocumentTypes(): bool
    {
        return DocumentType::count() === 0;
    }
};
