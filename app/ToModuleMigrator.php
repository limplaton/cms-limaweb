<?php
 

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Models\Filter;
use Modules\Core\App\Support\Makeable;
use Modules\Translator\App\Contracts\TranslationLoader;

/**
 * @codeCoverageIgnore
 */
class ToModuleMigrator
{
    use Makeable;

    protected string $moduleNameLower;

    public function __construct(string $moduleName)
    {
        $this->moduleNameLower = strtolower($moduleName);
    }

    public function migrateMorphs(string $oldModel, string $newModel): static
    {
        foreach ($this->morphTables() as $table => $column) {
            DB::table($table)->where($column, $oldModel)->update([
                $column => $newModel,
            ]);
        }

        return $this;
    }

    public function migrateMailableTemplates(array $map): static
    {
        if ($this->usingOldMailableTemplates(array_keys($map))) {
            foreach ($map as $old => $new) {
                // Delete previous seeded new templates as they are registered in the service provider
                DB::table('mailable_templates')->where('mailable', $new)->delete();

                DB::table('mailable_templates')->where('mailable', $old)->update(['mailable' => $new]);
            }
        }

        return $this;
    }

    public function migrateWorkflowTriggers(array $map): static
    {
        foreach ($map as $old => $new) {
            DB::table('workflows')->where('trigger_type', $old)->update([
                'trigger_type' => $new,
            ]);
        }

        return $this;
    }

    public function migrateWorkflowActions(array $map): static
    {
        foreach ($map as $old => $new) {
            DB::table('workflows')->where('action_type', $old)->update([
                'action_type' => $new,
            ]);
        }

        return $this;
    }

    public function migrateNotifications(array $map): static
    {
        foreach ($map as $old => $new) {
            DB::table('notifications')->where('type', $old)->update(['type' => $new]);
        }

        return $this;
    }

    protected function usingOldMailableTemplates(array $templates): bool
    {
        return DB::table('mailable_templates')
            ->whereIn('mailable', $templates)
            ->count() > 0;
    }

    public function migrateDbLanguageKeys(string $oldKey, ?string $newKey = null): static
    {
        $newKey ??= $oldKey;
        $namespace = $this->moduleNameLower;

        DB::table('notifications')->where('data', 'like', '%"key":"'.$oldKey.'.%')->update([
            'data' => DB::raw("REPLACE(data,'\"key\":\"".$oldKey.".','\"key\":\"".$namespace.'::'.$newKey.".')"),
        ]);

        DB::table('changelog')->where('properties', 'like', '%"key":"'.$oldKey.'.%')->update([
            'properties' => DB::raw("REPLACE(properties,'\"key\":\"".$oldKey.".','\"key\":\"".$namespace.'::'.$newKey.".')"),
        ]);

        Filter::where('name', 'like', $oldKey.'.%')->get(['id', 'name'])
            ->each(function (Filter $filter) use ($oldKey, $namespace, $newKey) {
                $filter->name = str_replace($oldKey, $namespace.'::'.$newKey, $filter->getRawOriginal('name'));
                $filter->save();
            });

        return $this;
    }

    public function migrateLanguageFiles(array $files): static
    {
        $namespace = $this->moduleNameLower;

        if ($this->usingOldLangOverrideFiles($files)) {
            $overridePath = app(TranslationLoader::class)->getOverridePath();

            foreach (Innoclapps::locales() as $locale) {
                $customLocalePath = $overridePath.DIRECTORY_SEPARATOR.$locale;

                File::ensureDirectoryExists($newOverridePath = $customLocalePath.DIRECTORY_SEPARATOR.'_'.$namespace);

                foreach ($files as $filename) {
                    $oldPath = $customLocalePath.DIRECTORY_SEPARATOR.$filename;

                    if (File::isFile($oldPath)) {
                        File::move(
                            $oldPath,
                            $newOverridePath.DIRECTORY_SEPARATOR.$filename
                        );
                    }
                }
            }
        }

        foreach (Innoclapps::locales() as $locale) {
            foreach ($files as $filename) {
                if (File::isFile($path = lang_path($locale.DIRECTORY_SEPARATOR.$filename))) {
                    File::delete($path);
                }
            }
        }

        return $this;
    }

    protected function usingOldLangOverrideFiles(array $files): bool
    {
        $overridePath = app(TranslationLoader::class)->getOverridePath();

        foreach (Innoclapps::locales() as $locale) {
            $customLocalePath = $overridePath.DIRECTORY_SEPARATOR.$locale;

            foreach ($files as $filename) {
                if (File::isFile($customLocalePath.DIRECTORY_SEPARATOR.$filename)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function deleteConflictedFiles(array $paths): static
    {
        foreach ($paths as $path) {
            if (File::isDirectory($path)) {
                File::deleteDirectory($path);
            } elseif (File::exists($path)) {
                File::delete($path);
            }
        }

        return $this;
    }

    protected function morphTables(): array
    {
        return [
            'activityables' => 'activityable_type',
            'callables' => 'callable_type',
            'noteables' => 'noteable_type',
            'changelog' => 'subject_type',
            'taggables' => 'taggable_type',
            'model_has_scheduled_emails' => 'model_type',
            'changelog' => 'causer_type',
            'contactables' => 'contactable_type',
            'dealables' => 'dealable_type',
            'documentables' => 'documentable_type',
            'email_account_messageables' => 'messageable_type',
            'guests' => 'guestable_type',
            'phones' => 'phoneable_type',
            'model_has_custom_field_options' => 'model_type',
            'meta' => 'metable_type',
            'model_visibility_groups' => 'visibilityable_type',
            'model_visibility_group_dependents' => 'dependable_type',
            'mediables' => 'mediable_type',
            'pinned_timeline_subjects' => 'subject_type',
            'pinned_timeline_subjects' => 'timelineable_type',
            'user_ordered_models' => 'orderable_type',
            'views' => 'viewable_type',
            'synchronizations' => 'synchronizable_type',
            'comments' => 'commentable_type',
            'billables' => 'billableable_type',
            'notifications' => 'notifiable_type',
            'personal_access_tokens' => 'tokenable_type',
            'model_has_roles' => 'model_type',
        ];
    }
}
