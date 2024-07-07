<?php
 

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Core\App\Contracts\Resources\Tableable;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Models\CustomField;
use Modules\Core\App\Updater\UpdatePatcher;
use Modules\Users\App\Models\User;

return new class extends UpdatePatcher
{
    protected array $fieldsToDelete = ['ColorSwatches', 'DropdownSelect', 'MailEditor', 'IntroductionField'];

    public function run(): void
    {
        foreach ($this->fieldsToDelete as $filename) {
            if (is_file(module_path('Core', 'App/Fields/'.$filename.'.php'))) {
                unlink(module_path('Core', 'App/Fields/'.$filename.'.php'));
            }
        }

        settings([
            '_last_cron_run' => settings('last_cron_run'),
            'last_cron_run' => null,
        ]);

        Innoclapps::registeredResources()
            ->whereInstanceOf(Tableable::class)
            ->each(function ($resource) {
                $request = app(ResourceRequest::class)->setResource($resource->name());
                $loggedInUser = Auth::user();
                foreach (User::get() as $user) {
                    $request->setUserResolver(fn () => $user);
                    Auth::setUser($user);
                    $table = $resource->resolveTable($request);

                    if (! $table->customizeable) {
                        continue;
                    }

                    if ($settings = $table->settings()->getCustomizedSettings()) {
                        foreach (['columns', 'order'] as $configKey) {
                            foreach ($settings[$configKey] as $key => $config) {
                                if (str_starts_with($config['attribute'], 'custom_field_')) {
                                    $fields = $resource->getFields()->filterCustomFields();

                                    $relatedField = $fields->first(function ($field) use ($config) {
                                        return Str::snake($field->customField->relationName, '_') === $config['attribute'];
                                    });

                                    if ($relatedField) {
                                        $settings[$configKey][$key]['attribute'] = $relatedField->attribute;
                                    }
                                }
                            }
                        }

                        $table->settings()->update($settings);
                    }
                }

                if ($loggedInUser && $loggedInUser->isNot(Auth::user())) {
                    Auth::setUser($loggedInUser);
                }
            });

        // Update old indexes with new.
        $uniqueCustomFields = CustomField::where('is_unique', true)->get();

        foreach ($uniqueCustomFields as $field) {
            $relatedModel = Innoclapps::resourceByName($field->resource_name)->newModel();

            $indexes = $this->getColumnIndexes($relatedModel->getTable(), $field->field_id);

            foreach ($indexes as $index) {
                if ($index['unique'] === true) {
                    Schema::table($relatedModel->getTable(), function (Blueprint $table) use ($index) {
                        $table->dropUnique($index['name']);
                    });

                    Schema::table($relatedModel->getTable(), function (Blueprint $table) use ($field) {
                        $table->unique($field->field_id, $field->uniqueIndexName());
                    });
                }
            }
        }
    }

    public function shouldRun(): bool
    {
        return collect($this->fieldsToDelete)
            ->some(fn ($filename) => is_file(module_path('Core', 'App/Fields/'.$filename.'.php')));
    }
};
