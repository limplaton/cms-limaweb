<?php
 

namespace Modules\WebForms\App\Http\Requests;

use Illuminate\Support\Facades\Auth;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Fields\Field;
use Modules\Core\App\Fields\FieldsCollection;
use Modules\Core\App\Http\Requests\InteractsWithResourceFields;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Resource\Resource;
use Modules\WebForms\App\Models\WebForm;

class WebFormRequest extends ResourceRequest
{
    use InteractsWithResourceFields {
        InteractsWithResourceFields::prepareForValidation as basePrepareForValidation;
    }

    /**
     * Original input for the request before any modifications.
     */
    protected array $formInput = [];

    /**
     * The web form instance for the current request.
     */
    protected ?WebForm $webForm = null;

    /**
     * Get the web form for the request.
     */
    public function webForm(): WebForm
    {
        if ($this->webForm) {
            return $this->webForm;
        }

        $webForm = WebForm::findByUuid($this->uuid());

        abort_if(! Auth::check() && ! $webForm->isActive(), 404);

        return $this->webForm = $webForm;
    }

    /**
     * Get the form uuid
     */
    public function uuid(): string
    {
        return $this->route('uuid');
    }

    /**
     * Set the resource name for the current request
     */
    public function setResource(string $resourceName): static
    {
        $this->resource = $this->findResource($resourceName);

        $this->setFields($this->allFields()->filter(function (Field $field) use ($resourceName) {
            return $field->meta()['resourceName'] === $resourceName;
        }));

        $this->replaceInputForCurrentResource();

        return $this;
    }

    /**
     * Replace the request input for the current resource.
     */
    protected function replaceInputForCurrentResource(): void
    {
        // When changing resource, the actual input shoud be replaced from the actual resource
        // available fields/files to avoid any conflicts when saving the records
        // e.q. a company may have name, as well deal may have name
        // when using in FormSubmissionService ->replace method, there may be conflicts

        /** @var array */
        $input = collect($this->webForm()->fileSections())->reduce(function (array $input, array $section) { // merge with initial
            $input[$section['requestAttribute']] = $this->formInput[$section['requestAttribute']];

            return $input;
        }, $this->getFields()->reduce(function (array $input, Field $field) { // initial
            $input[$field->requestAttribute] = $this->formInput[$field->requestAttribute];

            return $input;
        }, []));

        $this->replace($input);
    }

    /**
     * Get the resource for the request.
     */
    public function resource(): Resource
    {
        return $this->resource;
    }

    /**
     * Get the available resources based on the form sections with fields.
     */
    public function resources(): array
    {
        return $this->getFields()->unique(function (Field $field) {
            return $field->meta()['resourceName'];
        })->map(fn (Field $field) => $field->meta()['resourceName'])->values()->all();
    }

    /**
     * Get all the available fields for the request.
     */
    public function allFields(): FieldsCollection
    {
        return $this->webForm()->fields();
    }

    /**
     * Provide the fields available for the request.
     */
    public function fields(): FieldsCollection
    {
        return $this->allFields();
    }

    /**
     * Get the files sections that are required.
     */
    protected function requiredFileSections(): array
    {
        return collect($this->webForm()->fileSections())->where('isRequired', true)->all();
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function messages(): array
    {
        return array_merge(
            $this->messagesFromFields(), collect($this->requiredFileSections())->mapWithKeys(function (array $section) {
                return [$section['requestAttribute'].'.required' => __('validation.required_file')];
            })->all()
        );
    }

    /**
     * Prepare the request for validation.
     */
    public function prepareForValidation(): void
    {
        app()->setLocale($this->webForm()->locale);

        $this->basePrepareForValidation();

        $this->rememberFormInput();
    }

    /**
     * Remember the request input from all resources.
     */
    public function rememberFormInput(): static
    {
        $this->formInput = $this->all();

        return $this;
    }

    /**
     * Get the request input from all resources.
     */
    public function getFormInput(string|Field|null $key = null): mixed
    {
        $key = $key instanceof Field ? $key->requestAttribute() : $key;

        return is_string($key) ? $this->formInput[$key] ?? null : $this->formInput;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $rules = $this->allFields()->mapWithKeys(
            fn (Field $field) => $field->getCreationRules()
        )->all();

        if ($this->privacyPolicyAcceptIsRequired()) {
            $rules['_privacy-policy'] = 'accepted';
        }

        $this->addFileSectionValidationRules($rules);

        return $this->formatRules($rules);
    }

    /**
     * Add validation for the file sections.
     */
    protected function addFileSectionValidationRules(array &$rules): void
    {
        foreach ($this->requiredFileSections() as $section) {
            $attribute = $section['requestAttribute'];

            $rules[$attribute] = ['required'];

            if ($section['multiple']) {
                $rules[$attribute][] = 'array';
            }

            $rules[$attribute.($section['multiple'] ? '.*' : '')][] = 'max:'.config('mediable.max_size');
            $rules[$attribute.($section['multiple'] ? '.*' : '')][] = 'mimes:'.implode(',', Innoclapps::allowedUploadExtensions());
        }
    }

    /**
     * Indicates whether the privacy policy must be accepted.
     */
    protected function privacyPolicyAcceptIsRequired(): bool
    {
        return $this->webForm()->submitSection()['privacyPolicyAcceptIsRequired'] ?? false;
    }
}
