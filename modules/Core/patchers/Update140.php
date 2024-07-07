<?php
 

use App\ToModuleMigrator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Modules\Core\App\Updater\UpdatePatcher;
use Nwidart\Modules\Facades\Module;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        ToModuleMigrator::make('core')
            ->migrateMorphs('Modules\\Core\\Models\\CustomField', 'Modules\\Core\\App\\Models\\CustomField')
            ->migrateMorphs('Modules\\Core\\Models\\Filter', 'Modules\\Core\\App\\Models\\Filter')
            ->migrateMorphs('Modules\\Core\\Models\\Media', 'Modules\\Core\\App\\Models\\Media')
            ->migrateMorphs('Modules\\Core\\Models\\Role', 'Modules\\Core\\App\\Models\\Role')
            ->migrateMorphs('Modules\\Core\\Models\\Synchronization', 'Modules\\Core\\App\\Models\\Synchronization')
            ->migrateWorkflowActions([
                'Modules\\Core\\Workflow\\Actions\\WebhookAction' => 'Modules\\Core\\App\\Workflow\\Actions\\WebhookAction',
            ]);

        // Only primary key?
        if (count(Schema::getIndexes('mailable_templates')) === 1) {
            try {
                Schema::table('mailable_templates', function (Blueprint $table) {
                    $table->unique(['mailable', 'locale']);
                });
            } catch (PDOException) {
                // In case there were duplicate templates, do nothing.
            }
        }

        DB::table('mediables')->where('tag', 'profile')->update(['tag' => 'direct']);

        $renameOldMigrations = [];

        foreach (Module::core() as $module) {
            $name = $module->getName();

            if (is_dir($dbDir = module_path($name, 'Database'))) {
                $renameOldMigrations[$name] = [
                    'has_new_dir' => false,
                    'has_old_dir' => false,
                ];

                foreach (File::directories($dbDir) as $dir) {
                    if (basename($dir) === 'Migrations') {
                        $renameOldMigrations[$name]['has_old_dir'] = true;
                    }

                    if (basename($dir) === 'migrations') {
                        $renameOldMigrations[$name]['has_new_dir'] = true;
                    }
                }
            }

            $oldLangPath = module_path($name, 'resources/lang');

            if (is_dir($oldLangPath)) {
                File::moveDirectory($oldLangPath, module_path($name, 'resources/lang-old'));
            }
        }

        foreach ($renameOldMigrations as $name => $data) {
            if (! $data['has_new_dir'] && $data['has_old_dir']) {
                File::moveDirectory(module_path($name, 'Database/Migrations'), module_path($name, 'Database/migrations'));
            } elseif ($data['has_new_dir'] && $data['has_old_dir']) {
                File::moveDirectory(module_path($name, 'Database/Migrations'), module_path($name, 'Database/Migrations-old'));
            }
        }
    }

    public function shouldRun(): bool
    {
        return true;
    }
};
