<?php
 

use App\ToModuleMigrator;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        ToModuleMigrator::make('comments')
            ->migrateMorphs('App\\Models\\Comment', 'Modules\\Comments\\Models\\Comment')
            ->migrateLanguageFiles(['comment.php'])
            ->deleteConflictedFiles([app_path('Models/Comment.php')]);
    }

    public function shouldRun(): bool
    {
        return file_exists(app_path('Models/Comment.php'));
    }
};
