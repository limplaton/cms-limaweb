<?php
 

namespace Modules\WebForms\App\Services;

use Illuminate\Support\Arr;
use Modules\Contacts\App\Fields\Phone;
use Modules\Core\App\Contracts\Fields\Dateable;
use Modules\Core\App\Facades\ChangeLogger;
use Modules\Core\App\Fields\BelongsTo;
use Modules\Core\App\Fields\DateTime;
use Modules\Core\App\Fields\Field;
use Modules\Core\App\Models\Changelog;
use Modules\WebForms\App\Http\Requests\WebFormRequest;

class FormSubmissionLogger
{
    /**
     * Changelog identifier
     */
    const IDENTIFIER = 'web-form-submission-changelog';

    /**
     * Initialize new FormSubmissionLogger instance
     */
    public function __construct(protected array $models, protected WebFormRequest $request)
    {
    }

    /**
     * Log the submission changelog.
     */
    public function log(): Changelog
    {
        foreach ($this->models as $model) {
            $lastChangelog = ChangeLogger::useModelLog()
                ->on($model)
                ->forceLogging()
                ->byAnonymous()
                ->identifier(static::IDENTIFIER)
                ->description($this->request->webForm()->title)
                ->withProperties($this->getProperties())
                ->log();
        }

        return $lastChangelog;
    }

    /**
     * Get the changelog properties for the given model.
     */
    protected function getProperties(): array
    {
        return array_merge(
            $this->propertiesFromFieldSections(),
            $this->propertiesFromFileSections()
        );
    }

    /**
     * Get the changelog properties from the field sections.
     */
    protected function propertiesFromFieldSections(): array
    {
        return $this->request->webForm()->fields()->map(function (Field $field) {
            $attributes = [
                'value' => $this->request->getFormInput($field),
                'attribute' => $field->attribute,
                'label' => $field->label,
                'resourceName' => $field->meta()['resourceName'],
            ];

            if (! blank($attributes['value'])) {
                if ($field instanceof Dateable) {
                    // Dates must be formatted on front-end for proper display in user timezone
                    $attributes[$field instanceof DateTime ? 'dateTime' : 'date'] = true;
                } else {
                    $attributes['value'] = $this->displayValueFromField($attributes['value'], $field);
                }
            }

            $attributes['value'] = ! blank($attributes['value']) ? $attributes['value'] : null;

            return $attributes;
        })->all();
    }

    /**
     * Get the changelog properties from the file sections.
     */
    protected function propertiesFromFileSections(): array
    {
        $sections = $this->request->webForm()->fileSections();

        return collect($sections)->map(function (array $section) {
            $attributes = [
                'value' => [],
                'label' => $section['label'],
                'resourceName' => $section['resourceName'],
            ];

            foreach (Arr::wrap($this->request->getFormInput($section['requestAttribute']) ?? []) as $file) {
                $attributes['value'][] = $file->getClientOriginalName().' ('.format_bytes($file->getSize()).')';
            }

            $attributes['value'] = count($attributes['value']) > 0 ? implode(', ', $attributes['value']) : null;

            return $attributes;
        })->all();
    }

    /**
     * Get the display value from the field.
     */
    protected function displayValueFromField(mixed $value, Field $field): mixed
    {
        if ($field instanceof BelongsTo) {
            $value = $field->getModel()->find($value)->{$field->labelKey};
        } elseif ($field instanceof Phone) {
            $value = collect($this->request->getFormInput($field))->pluck('number')->implode(', ');
        } elseif ($field->isOptionable()) {
            $value = $this->displayValueWhenOptionableField($field, $value);
        }

        return $value;
    }

    /**
     * Get display value when the field is multi optionable.
     *
     * @param  \Modules\Core\App\Fields\Field|\Modules\Core\App\Fields\Optionable  $field
     * @param  mixed  $value
     * @return string
     */
    protected function displayValueWhenMultioptionableField($field, $value)
    {
        return $field->getCachedOptions()
            ->whereIn($field->valueKey, $value)
            ->pluck($field->labelKey)
            ->implode(', ');
    }

    /**
     * Get the value when optionable field.
     *
     * @param  \Modules\Core\App\Fields\Field|\Modules\Core\App\Fields\Optionable  $field
     * @param  mixed  $value
     * @return string
     */
    protected function displayValueWhenOptionableField($field, $value)
    {
        if ($field->isMultiOptionable()) {
            return $this->displayValueWhenMultioptionableField($field, $value);
        }

        return $field->getKeyFromOption($field->optionByKey($value), $field->labelKey);
    }
}
