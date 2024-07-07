<?php
 

namespace Modules\Core\App\Fields;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Resource\Resource;
use Modules\Core\App\Support\HasOptions;

class Optionable extends Field
{
    use ConfiguresOptions,
        HasOptions {
            HasOptions::resolveOptions as baseResolveOptions;
        }

    protected ?Collection $cachedOptions = null;

    protected ?Collection $allCachedOptions = null;

    public bool $acceptLabelAsValue = false;

    /**
     * Resolve the element options.
     */
    public function resolveOptions(): array
    {
        if ($this->shoulUseZapierOptions()) {
            return $this->formatOptions($this->zapierOptions());
        }

        return $this->baseResolveOptions();
    }

    /**
     * Accept string value.
     */
    public function acceptLabelAsValue(): static
    {
        $this->acceptLabelAsValue = true;

        $this->prepareForValidation(function (mixed $value, ResourceRequest $request, Validator $validator) {
            if (is_null($value)) {
                return $value;
            }

            if ($this->isMultiOptionable()) {
                [$valid, $invalid] = $this->parseValueAsLabelViaMultiOptionable($value, $request);

                if (($totalInvalid = count($invalid)) > 0) {
                    $this->addInvalidOptionValidationError($valid, trans_choice('validation.invalid_options', $totalInvalid, [
                        'options' => collect($invalid)->implode(', '),
                    ]));
                }

                return $valid;
            } else {
                // Accepts single options.
                if ($option = $this->optionByLabel($value)) {
                    // Provided option exists as a label.
                    $value = $this->getKeyFromOption($option);
                } elseif (! $this->optionByKey($value)) {
                    // Provided option is key and does not exists, fail.
                    $this->addInvalidOptionValidationError($validator);
                }

                return $value;
            }
        });

        return $this;
    }

    /**
     * Get the sample value for the field.
     */
    public function sampleValue(): mixed
    {
        if (is_callable($this->sampleValueCallback)) {
            return call_user_func_array($this->sampleValueCallback, [$this->attribute]);
        }

        return $this->resolveOptions()[0][
           $this->acceptLabelAsValue ? $this->labelKey : $this->valueKey
        ] ?? '';
    }

    /**
     * Get cached options collection.
     *
     * When importing data, the label as value function will be called
     * multiple times, we don't want all the queries executed multiple times
     * from the fields which are providing options from the database.
     */
    public function getCachedOptions(): Collection
    {
        return $this->cachedOptions ??= collect($this->resolveOptions());
    }

    /**
     * Get cached options collection.
     *
     * When importing data, the label as value function will be called
     * multiple times, we don't want all the queries executed multiple times
     * from the fields which are providing options from the database.
     */
    public function getAllCachedOptions(): Collection
    {
        return $this->allCachedOptions ??= collect($this->resolveAllOptions());
    }

    /**
     * Clear the cached options collections.
     */
    public function clearCachedOptions(): static
    {
        $this->cachedOptions = null;
        $this->allCachedOptions = null;

        return $this;
    }

    /**
     * Get option by given label.
     *
     * @param  string  $label
     * @return mixed
     */
    public function optionByLabel($label, ?Collection $options = null)
    {
        // Trimming is applied to account for data that might not be trimmed in database entries
        // due to the absence of the "\Maatwebsite\Excel\Middleware\TrimCellValue" middleware in versions prior to 1.3.4.

        $normalizeLabel = function ($value) {
            return is_string($value) ? trim(strtolower($value)) : $value;
        };

        $label = $normalizeLabel($label);
        $options = $options ?? $this->getCachedOptions();

        return $options->first(function ($option) use ($label, $normalizeLabel) {
            $optionLabel = $normalizeLabel($this->getKeyFromOption($option, $this->labelKey));

            return $optionLabel === $label;
        });
    }

    /**
     * Find an option by the given key.
     */
    public function optionByKey($value, ?Collection $options = null)
    {
        return ($options ?? $this->getCachedOptions())->first(function ($option) use ($value) {
            $key = $this->getKeyFromOption($option);

            return $key == $value;
        });
    }

    /**
     * Find option by key or label.
     */
    public function optionByKeyOrLabel(mixed $value, ?Collection $options = null): object|array|null
    {
        if ($option = $this->optionByKey($value, $options)) {
            return $option;
        }

        if ($option = $this->optionByLabel($value, $options)) {
            return $option;
        }

        return null;
    }

    /**
     * Find option by key or label from the non filtered options.
     */
    protected function optionByKeyOrLabelFromNonFilteredOptions(mixed $value): object|array|null
    {
        // It may happen the field to have filtered options for the specific user
        // in this case, we will check whether the field provides the
        // non filtered options and check if the provided value exists in the non filtered options
        // in this case, we will allow the value to be saved in the database.
        // e.q. user with ability to edit activity but this user is allowed to see his own team users only
        // but the activity is assigned to a user from different team that the current user does not belongs to.
        return $this->optionByKeyOrLabel($value, $this->getAllCachedOptions());
    }

    /**
     * Get key from the given object.
     */
    public function getKeyFromOption(object|array $option, ?string $key = null): mixed
    {
        $key = $key ?? $this->valueKey;

        return is_array($option) ? $option[$key] : $option->{$key};
    }

    /**
     * Get the field value when label is provided for multi optionable fields.
     */
    public function parseValueAsLabelViaMultiOptionable(array|string|int $value): array
    {
        // valid, invalid
        $ids = [[], []];

        if (is_string($value)) {
            $value = Str::of($value)->explode(',')->trim()->all();
        } elseif (is_int($value)) {
            $value = [$value];
        }

        foreach ($value as $id) {
            if ($option = $this->optionByLabel($id)) {
                $ids[0][] = $this->getKeyFromOption($option);
            } elseif (! $this->optionByKey($id)) {
                $ids[1][] = $id;
            } else {
                $ids[0][] = $id;
            }
        }

        $ids[0] = array_unique(array_filter($ids[0]));

        return $ids;
    }

    /**
     * Add invalid option error to the given validator.
     */
    protected function addInvalidOptionValidationError(Validator $validator, ?string $message = null): void
    {
        $validator->after(function (Validator $validator) use ($message) {
            $validator->errors()->add(
                $this->attribute, $message ?? __('validation.exists', ['attribute' => $this->label])
            );
        });
    }

    /**
     * Check whether the Zapier options should be used.
     */
    protected function shoulUseZapierOptions(): bool
    {
        return Request::isZapier() && method_exists($this, 'zapierOptions');
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'acceptLabelAsValue' => $this->acceptLabelAsValue,
            'valueKey' => $this->valueKey,
            'labelKey' => $this->labelKey,
            'optionsViaResource' => $this->options instanceof Resource ? $this->options->name() : null,
            'options' => $this->resolveOptions(),
        ]);
    }
}
