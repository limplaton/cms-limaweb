<?php
 

use App\ToModuleMigrator;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        ToModuleMigrator::make('webforms')
            ->migrateMailableTemplates([
                'Modules\\WebForms\\Mail\\WebFormSubmitted' => 'Modules\WebForms\App\Mail\WebFormSubmitted',
            ]);
    }

    public function shouldRun(): bool
    {
        return true;
    }
};
