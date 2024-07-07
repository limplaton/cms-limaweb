<?php
 

use App\ToModuleMigrator;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        ToModuleMigrator::make('notes')
            ->migrateMorphs('App\\Models\\Note', 'Modules\\Notes\\Models\\Note')
            ->migrateDbLanguageKeys('note')
            ->migrateLanguageFiles(['note.php'])
            ->deleteConflictedFiles($this->getConflictedFiles());
    }

    public function shouldRun(): bool
    {
        return file_exists(app_path('Models/Note.php'));
    }

    protected function getConflictedFiles(): array
    {
        return [
            app_path('Resources/Note'),
            app_path('Models/Note.php'),
        ];
    }
};
