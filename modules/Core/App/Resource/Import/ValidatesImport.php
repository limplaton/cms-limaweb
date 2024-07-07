<?php
 

namespace Modules\Core\App\Resource\Import;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator as LaravelValidator;
use Modules\Core\App\Fields\Field;
use Modules\Core\App\Http\Requests\ImportRequest;
use Modules\Core\App\Rules\UniqueResourceRule;

trait ValidatesImport
{
    /**
     * Validate the given request.
     */
    protected function validate(LaravelValidator $validator, ImportRequest $request): ?bool
    {
        try {
            $validator->validate();
        } catch (ValidationException $e) {
            $failures = [];

            foreach ($e->errors() as $attribute => $messages) {
                $failures[] = new Failure(
                    $request->getRowNumber(),
                    $attribute,
                    (array) $messages,
                    $request->original(),
                );
            }

            $this->onFailure(...$failures);

            throw new RowSkippedException(...$failures);
        }

        return true;
    }

    /**
     * Prepare the validator for the given data.
     */
    protected function createValidator(ImportRequest $request): LaravelValidator
    {
        return Validator::make(
            $request->all(),
            $this->rules($request),
            $this->customValidationMessages(),
            $this->customValidationAttributes()
        );
    }

    /**
     * Provide custom error messages for import.
     */
    public function customValidationMessages(): array
    {
        return $this->getFields()->map(function (Field $field) {
            return $field->prepareValidationMessages();
        })->filter()
            ->collapse()
            ->mapWithKeys(function ($message, $attribute) {
                return [$attribute => $message];
            })
            ->all();
    }

    /**
     * Provide custom attributes for the validation rules.
     */
    public function customValidationAttributes(): array
    {
        return $this->getFields()->mapWithKeys(function (Field $field) {
            return [$field->attribute => Str::lower(strip_tags($field->label))];
        })->all();
    }

    /**
     * Provide the import validation rules.
     */
    public function rules(ImportRequest $request): array
    {
        $formatted = [];

        foreach ($this->getFields() as $field) {
            $rules = $field->getImportRules();
            $attributes = array_keys($rules);

            foreach ($attributes as $attribute) {
                $formatted[$attribute] = collect($rules[$attribute])->reject(
                    fn ($rule) => $rule instanceof UniqueResourceRule && $rule->skipOnImport
                )->all();
            }
        }

        return $request->formatRules($formatted);
    }

    /**
     * Handle row validation failure.
     */
    protected function onFailure(Failure ...$failures)
    {
        static::$skipped++;

        $this->failures = array_merge($this->failures, $failures);
    }

    /**
     * @return Failure[]|\Illuminate\Support\Collection
     */
    public function failures(): Collection
    {
        return new class($this->failures) extends Collection
        {
            public function toArray()
            {
                return $this->map(fn (Failure $failure) => [
                    'row' => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'errors' => $failure->errors(),
                    'values' => $failure->values(),
                ])->all();
            }
        };
    }
}
