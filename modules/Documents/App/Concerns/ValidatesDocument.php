<?php
 

namespace Modules\Documents\App\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Billable\App\Enums\TaxType;
use Modules\Brands\App\Models\Brand;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Rules\StringRule;
use Modules\Core\App\Rules\ValidLocaleRule;
use Modules\Core\App\Rules\VisibleModelRule;
use Modules\Documents\App\Enums\DocumentViewType;
use Modules\Documents\App\Models\DocumentType;

trait ValidatesDocument
{
    /**
     * Set the resource rules available for create and update
     */
    public function rules(ResourceRequest $request): array
    {
        return [
            'title' => [$request->isMethod('POST') ? 'required' : 'filled', StringRule::make()],
            'document_type_id' => [
                (new VisibleModelRule(new DocumentType))->ignore(
                    fn () => $request->isUpdateRequest() ? $request->record()->type : null
                ),
                $request->isMethod('POST') ? Rule::requiredIf(
                    function () {
                        $defaultId = DocumentType::getDefaultType();

                        if (is_null($defaultId)) {
                            return true;
                        }

                        $type = DocumentType::find($defaultId);

                        /** @var \Modules\Users\App\Models\User */
                        $user = Auth::user();

                        return $type ? $user->cant('view', $type) : true;
                    }
                ) : 'filled'],

            'user_id' => $request->isMethod('POST') ? 'required' : 'filled',

            'requires_signature' => ['nullable', 'boolean'],

            // If brand field added e.q. to show on table, move the validation to the field
            'brand_id' => [
                'bail', 'exists:brands,id',
                $request->isMethod('POST') ? 'required' : 'filled',
                (new VisibleModelRule(new Brand))->ignore(fn () => $request->isUpdateRequest() ? $request->record()->brand : null),
            ],

            'view_type' => ['nullable', Rule::enum(DocumentViewType::class)],

            'locale' => ['sometimes', 'required', 'string', new ValidLocaleRule],

            'pdf.font' => [
                'nullable',
                'string',
                Rule::in(Arr::pluck(config('contentbuilder.fonts'), 'font-family')),
            ],

            'pdf.orientation' => ['nullable', 'string', 'in:portrait,landscape'],
            'pdf.size' => ['nullable', 'string', 'in:a4,letter'],

            'send' => ['nullable', 'boolean'],
            'send_mail_account_id' => 'required_if:send,true',
            'send_mail_subject' => 'required_if:send,true',
            'send_mail_body' => 'required_if:send,true',

            'signers' => ['nullable', 'array'],
            'signers.*.name' => ['required', StringRule::make()],
            'signers.*.email' => ['required', StringRule::make(), 'email'],

            'recipients' => ['nullable', 'array'],
            'recipients.*.name' => ['required', StringRule::make()],
            'recipients.*.email' => ['required', StringRule::make(), 'email'],

            'billable.tax_type' => ['nullable', 'string', Rule::in(TaxType::names())],
            'billable.products.*.name' => ['sometimes', 'required', StringRule::make()],
            'billable.products.*.discount_type' => ['nullable', 'string', 'in:fixed,percent'],
            'billable.products.*.display_order' => 'integer',
            'billable.products.*.qty' => ['nullable', 'regex:/^[0-9]\d*(\.\d{0,2})?$/'],
            'billable.products.*.unit' => ['nullable', StringRule::make()],
            'billable.products.*.tax_label' => ['nullable', StringRule::make()],
            'billable.products.*.tax_rate' => ['nullable', 'numeric', 'decimal:0,3', 'min:0'],
            'billable.products.*.product_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * Get the custom validation messages for the resource
     * Useful for resources without fields.
     */
    public function validationMessages(): array
    {
        return [
            'title.required' => __('validation.required', [
                'attribute' => Str::lower(__('documents::document.title')),
            ]),
            'title.string' => __('validation.string', [
                'attribute' => Str::lower(__('documents::document.title')),
            ]),

            'document_type_id.filled' => __('validation.filled', [
                'attribute' => Str::lower(__('documents::document.type.type')),
            ]),
            'document_type_id.required' => __('validation.required', [
                'attribute' => Str::lower(__('documents::document.type.type')),
            ]),

            'signers.*.name.required' => __('validation.required', [
                'attribute' => Str::lower(__('documents::document.signers.name')),
            ]),
            'signers.*.email.email' => __('validation.email', [
                'attribute' => Str::lower(__('documents::document.signers.email')),
            ]),
            'signers.*.email.required' => __('validation.required', [
                'attribute' => Str::lower(__('documents::document.signers.email')),
            ]),
            'signers.*.name.string' => __('validation.string', [
                'attribute' => Str::lower(__('documents::document.signers.name')),
            ]),
            'signers.*.name.max' => __('validation.max.string', [
                'attribute' => Str::lower(__('documents::document.signers.name')),
            ]),

            'recipients.*.name.required' => __('validation.required', [
                'attribute' => Str::lower(__('documents::document.recipients.name')),
            ]),
            'recipients.*.email.email' => __('validation.email', [
                'attribute' => Str::lower(__('documents::document.recipients.email')),
            ]),
            'recipients.*.email.required' => __('validation.required', [
                'attribute' => Str::lower(__('documents::document.recipients.email')),
            ]),
            'recipients.*.name.string' => __('validation.string', [
                'attribute' => Str::lower(__('documents::document.recipients.name')),
            ]),
            'recipients.*.name.max' => __('validation.max.string', [
                'attribute' => Str::lower(__('documents::document.recipients.name')),
            ]),
        ];
    }
}
