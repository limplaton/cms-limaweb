<?php
 

namespace Modules\Core\App\Common\Changelog;

use Closure;
use Spatie\Activitylog\Contracts\LoggablePipe;
use Spatie\Activitylog\EventLogBag;

class CustomFieldsChangesLogger implements LoggablePipe
{
    protected static array $oldCustomFieldOptionValues = [];

    /**
     * Initialize new CustomFieldsChangesLogger instance.
     */
    public function __construct(string $modelName)
    {
        $this->trackMultiOptionableCustomFieldChanges($modelName);
    }

    /**
     * Handle the pipe
     */
    public function handle(EventLogBag $event, Closure $next): EventLogBag
    {
        if (method_exists($event->model, 'getCustomFields')) {
            $customFields = $event->model->getCustomFields();

            $event->changes = collect($event->changes)->map(
                fn ($props) => $this->formatRegularAndSingleOptionableFieldProperties($props, $customFields)
            )->all();
        }

        return $next($event);
    }

    /**
     * Register event listener to track the multie optionable custom fields changes.
     */
    protected function trackMultiOptionableCustomFieldChanges(string $modelName): void
    {
        $modelName::beforeSyncCustomFieldOptions($this->beforeSyncCustomFieldOptionsCallback($modelName));
        $modelName::afterSyncCustomFieldOptions($this->afterSyncCustomFieldOptionsCallback($modelName));
    }

    /**
     * Get the callback for before sync custom field options
     */
    protected function beforeSyncCustomFieldOptionsCallback(string $modelName): Closure
    {
        return function ($model, $field, $attributes, $action) use ($modelName) {
            if ($action == 'update') {
                static::$oldCustomFieldOptionValues[$modelName][$field->attribute] = $field->resolveForDisplay($model);
            }
        };
    }

    /**
     * Get the callback for after sync custom field options.
     */
    protected function afterSyncCustomFieldOptionsCallback(string $modelName): Closure
    {
        return function ($model, $field, $attributes, $action) use ($modelName) {
            if ($action == 'update') {
                $newValue = $field->resolveForDisplay(
                    $model->load($field->customField->relationName)
                );

                $oldValue = static::$oldCustomFieldOptionValues[$modelName][$field->attribute];

                if ($newValue == $oldValue) {
                    return;
                }

                static::logMultiOptionableChangelog($newValue, $oldValue, $model, $field);
            }
        };
    }

    /**
     * Log multioptionable custom field options changed activity
     *
     * @param  string  $newValue
     * @param  string  $oldValue
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  \Modules\Core\App\Contracts\Fields\Customfieldable&\Modules\Core\App\Fields\Field  $field
     * @return void
     */
    protected static function logMultiOptionableChangelog($newValue, $oldValue, $model, $field)
    {
        $model->logDirtyAttributesOnLatestLog([
            'attributes' => [
                $field->attribute => [
                    'label' => $field->label,
                    'value' => $newValue,
                ],
            ],
            'old' => [
                $field->attribute => [
                    'label' => $field->label,
                    'value' => $oldValue,
                ],
            ],
        ], $model);
    }

    /**
     * Ensures custom fields properties are properly logged
     * Used for single optionable and regular custom field
     *
     * @param  array  $properties
     * @param  \Illuminate\Support\Collection  $customFields
     * @return array
     */
    protected function formatRegularAndSingleOptionableFieldProperties($properties, $customFields)
    {
        $ids = $customFields->pluck('field_id')->all();

        foreach (array_keys($properties) as $property) {
            if (! in_array($property, $ids)) {
                continue;
            }

            if (! $customField = $customFields->firstWhere('field_id', $property)) {
                continue;
            }

            // Custom fields are formatted with label and value keys
            // Because if a user delete a custom field, the label will be lost
            // and we updated activity won't be shown properly without the label
            // In this case, we keep the label in the activity itself
            $properties[$property] = [
                'label' => $customField->label,
                'value' => with($properties[$property], function ($value) use ($customField) {
                    if ($customField->isOptionable()) {
                        return $customField->options->find($value)?->name ?? $value;
                    }

                    return $value;
                }),
            ];
        }

        return $properties;
    }
}
