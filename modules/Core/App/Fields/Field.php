<?php
 

namespace Modules\Core\App\Fields;

use Closure;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Support\Arr;
use JsonSerializable;
use Modules\Core\App\Common\Placeholders\GenericPlaceholder;
use Modules\Core\App\Contracts\Fields\Dateable;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Http\Resources\CustomFieldResource;
use Modules\Core\App\Models\CustomField;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Rules\StringRule;
use Modules\Core\App\Rules\UniqueResourceRule;
use Modules\Core\App\Support\HasHelpText;
use Modules\Core\App\Support\Makeable;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

abstract class Field extends FieldElement implements JsonSerializable
{
    use DisplaysOnIndex,
        HasHelpText,
        Makeable,
        ResolvesValue;

    /**
     * Default value
     *
     * @var mixed
     */
    public $value;

    /**
     * Field attribute / column name
     *
     * @var string
     */
    public $attribute;

    /**
     * Custom field request attribute
     *
     * @var string|null
     */
    public $requestAttribute;

    /**
     * Field label
     *
     * @var string
     */
    public $label;

    /**
     * Indicates how the help text is displayed, as icon or text
     */
    public string $helpTextDisplay = 'icon';

    /**
     * Whether the field is collapsed. E.q. view all fields
     */
    public bool $collapsed = false;

    /**
     * Validation rules
     */
    public array $rules = [];

    /**
     * Validation creation rules
     */
    public array $creationRules = [];

    /**
     * Validation import rules
     */
    public array $importRules = [];

    /**
     * Validation update rules
     */
    public array $updateRules = [];

    /**
     * Custom validation error messages
     */
    public array $validationMessages = [];

    /**
     * Whether the field is primary
     */
    public bool $primary = false;

    /**
     * Indicates whether the field is custom field
     */
    public ?CustomField $customField = null;

    /**
     * Emit change event when field value changed
     */
    public ?string $emitChangeEvent = null;

    /**
     * Is field read only
     *
     * @var bool|callable
     */
    public $readOnly = false;

    /**
     * Is the field hidden
     */
    public bool $displayNone = false;

    /**
     * Indicates whether the column value should be always included in the
     * JSON Resource for front-end
     */
    public bool $alwaysInJsonResource = false;

    /**
     * Prepare for validation callback
     *
     * @var callable|null
     */
    public $validationCallback;

    /**
     * Indicates whether the field is excluded from Zapier response
     */
    public bool $excludeFromZapierResponse = false;

    /**
     * Field order
     */
    public ?int $order;

    /**
     * Field column class (full|half)
     */
    public string $width = 'full';

    /**
     * Field toggle indicator
     */
    public bool $toggleable = false;

    /**
     * Custom callback used to determine if the field is required.
     *
     * @var \Closure|bool
     */
    public $isRequiredCallback;

    /**
     * Indicates whether field label is hidden on forms.
     */
    public bool $hideLabel = false;

    /**
     * Indicates whether a unique field can be unmarked as unique
     */
    public bool $canUnmarkUnique = false;

    /**
     * Indicates that the field is available only for authRequired user.
     */
    public bool $authRequired = false;

    /**
     * The inline edit popover width (medium|large).
     */
    public string $inlineEditPanelWidth = 'medium';

    /**
     * Custom check if inline edit is disabled.
     *
     * @var bool|callable
     */
    public $disableInlineEdit = false;

    /**
     * Indicates whether the field is excluded from the special "bulk edit" action.
     */
    public bool $excludeFromBulkEdit = false;

    /**
     * Custom fill callback.
     *
     * @var null|callable
     */
    public $fillCallback = null;

    /**
     * The search column for the field.
     */
    public null|string|array|Expression $searchColumn = null;

    /**
     * Field component.
     */
    public static $component = null;

    /**
     * Additional relationships to eager load when quering the resource.
     */
    public array $with = [];

    /**
     * Indicates if the field is excluded from index query.
     */
    public bool $isExcludedFromIndexQuery = false;

    /**
     * Indicates if the field is searchable.
     */
    protected bool $searchable = true;

    protected static array $formComponents = [];

    protected static array $detailComponents = [];

    protected static array $indexComponents = [];

    protected Field|array $inlineEditWith = [];

    protected static ?ResourceRequest $request = null;

    /**
     * Initialize new Field instance class
     *
     * @param  string  $attribute  field attribute
     * @param  string|null  $label  field label
     */
    public function __construct($attribute, $label = null)
    {
        $this->attribute = $attribute;

        $this->label = $label;
    }

    /**
     * Set field attribute
     *
     * @param  string  $attribute
     */
    public function attribute($attribute): static
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * Set field label
     *
     * @param  string  $label
     */
    public function label($label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Set the field order
     */
    public function order(?int $order): static
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Set the field width for the form view
     */
    public function width(string $width): static
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Mark the field as toggleable
     */
    public function toggleable(bool $value = true): static
    {
        $this->toggleable = $value;

        return $this;
    }

    /**
     * Set default value on creation forms
     *
     * @param  mixed  $value
     */
    public function withDefaultValue($value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Disable the field inline edit.
     */
    public function disableInlineEdit(bool|callable $value = true): static
    {
        $this->disableInlineEdit = $value;

        return $this;
    }

    /**
     * Check whether inline edit is disabled for the given model.
     */
    public function isInlineEditDisabled(Model $model): bool
    {
        if ($this->disableInlineEdit === true) {
            return true;
        }

        return is_callable($this->disableInlineEdit) && call_user_func_array($this->disableInlineEdit, [$model]);
    }

    /**
     * Get the field default value
     */
    public function defaultValue(ResourceRequest $request): mixed
    {
        return with($this->value, function ($value) use ($request) {
            if ($value instanceof Closure) {
                return $value($request);
            }

            return $value;
        });
    }

    /**
     * Set collapsible field
     */
    public function collapsed(bool $bool = true): static
    {
        $this->collapsed = $bool;

        return $this;
    }

    /**
     * Set the field display of the help text
     */
    public function helpDisplay(string $display): static
    {
        $this->helpTextDisplay = $display;

        return $this;
    }

    /**
     * Add read only statement
     */
    public function readOnly(bool|callable $value): static
    {
        $this->readOnly = $value;

        return $this;
    }

    /**
     * Determine whether the field is read only
     */
    public function isReadOnly(): bool
    {
        $callback = $this->readOnly;

        return $callback === true || (is_callable($callback) && call_user_func($callback));
    }

    /**
     * Hides the field from the document
     */
    public function displayNone(bool $value = true): static
    {
        $this->displayNone = $value;

        return $this;
    }

    /**
     * Indicates whether the field label should be displayed.
     */
    public function hideLabel(bool $value = true): static
    {
        $this->hideLabel = $value;

        return $this;
    }

    /**
     * Get the component name for the field.
     */
    public function component(): ?string
    {
        return static::$component;
    }

    /**
     * Get the field form component.
     */
    public function formComponent(): ?string
    {
        if (isset(static::$formComponents[static::class])) {
            return static::$formComponents[static::class];
        }

        if (! static::$component) {
            return null;
        }

        return 'form-'.static::$component;
    }

    /**
     * Get the field detail component.
     */
    public function detailComponent(): ?string
    {
        if (isset(static::$detailComponents[static::class])) {
            return static::$detailComponents[static::class];
        }

        if (! static::$component) {
            return null;
        }

        return 'detail-'.static::$component;
    }

    /**
     * Get the field index component.
     */
    public function indexComponent(): ?string
    {
        if (isset(static::$indexComponents[static::class])) {
            return static::$indexComponents[static::class];
        }

        if (! static::$component) {
            return null;
        }

        return 'index-'.static::$component;
    }

    /**
     * Get the fields to be used when editing inline.
     *
     * By default, it's the current instance.
     */
    public function inlineEditField(): null|array|Field
    {
        if (! empty($this->inlineEditWith)) {
            return $this->inlineEditWith;
        }

        return null;
    }

    /**
     * Add custom fields to perform inline edit.
     */
    public function inlineEditWith(Field|array $field)
    {
        $this->inlineEditWith = $field;

        return $this;
    }

    /**
     * Change the underlying field form component.
     */
    public static function useFormComponent(string $component): void
    {
        static::$formComponents[static::class] = $component;
    }

    /**
     * Change the underlying field detail component.
     */
    public static function useDetailComponent(string $component): void
    {
        static::$detailComponents[static::class] = $component;
    }

    /**
     * Change the underlying field index component.
     */
    public static function useIndexComponent(string $component): void
    {
        static::$indexComponents[static::class] = $component;
    }

    /**
     * Set the field as primary
     */
    public function primary(bool $bool = true): static
    {
        $this->primary = $bool;

        return $this;
    }

    /**
     * Check whether the field is primary
     */
    public function isPrimary(): bool
    {
        return $this->primary === true;
    }

    /**
     * Set the callback used to determine if the field is required.
     *
     * Useful when you have complex required validation requirements like filled, sometimes etc..,
     * you can manually mark the field as required by passing a boolean when defining the field.
     *
     * This method will only add a "required" indicator to the UI field.
     * You must still define the related requirement rules() that should apply during validation.
     *
     * @param  \Closure|bool  $callback
     */
    public function required($callback = true): static
    {
        $this->isRequiredCallback = $callback;

        return $this;
    }

    /**
     * Check whether the field is required based on the rules
     */
    public function isRequired(ResourceRequest $request): bool
    {
        $callback = $this->isRequiredCallback;

        if ($callback === true || (is_callable($callback) && call_user_func($callback, $request))) {
            return true;
        }

        if (! empty($this->attribute)) {
            if ($request->isCreateRequest()) {
                $rules = $this->getCreationRules()[$this->requestAttribute()] ?? [];
            } elseif ($request->isUpdateRequest()) {
                $rules = $this->getUpdateRules()[$this->requestAttribute()] ?? [];
            } elseif ($request->isImportRequest()) {
                $rules = $this->getImportRules($request)[$this->requestAttribute()] ?? [];
            } else {
                $rules = $this->getRules()[$this->requestAttribute()] ?? [];
            }

            return in_array('required', $rules);
        }

        return false;
    }

    /**
     * Check whether the field is unique
     */
    public function isUnique(): bool
    {
        foreach ($this->getRules() as $rules) {
            if (collect($rules)->whereInstanceOf(UniqueResourceRule::class)->isNotEmpty()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mark the field as unique
     */
    public function unique($model, $skipOnImport = true): static
    {
        $this->rules(UniqueResourceRule::make($model)->skipOnImport($skipOnImport));

        return $this;
    }

    /**
     * Mark the field as not unique
     */
    public function notUnique(): static
    {
        foreach ($this->getRules() as $rules) {
            foreach ($rules as $ruleKey => $rule) {
                if ($rule instanceof UniqueResourceRule) {
                    unset($this->rules[$ruleKey]);
                }
            }
        }

        return $this;
    }

    /**
     * Mark that the a unique field can be marked as not unique via settings
     */
    public function canUnmarkUnique(): static
    {
        $this->canUnmarkUnique = true;

        return $this;
    }

    /**
     * Get the mailable template placeholder
     *
     * @param  \Modules\Core\App\Models\Model|null  $model
     * @return \Modules\Core\App\Common\Placeholders\GenericPlaceholder|string
     */
    public function mailableTemplatePlaceholder($model)
    {
        return GenericPlaceholder::make($this->attribute)
            ->description($this->label)
            ->value(function () use ($model) {
                return $this->resolveForDisplay($model);
            });
    }

    /**
     * Provide a callable to prepare the field for validation
     *
     * @param  callable  $callable
     */
    public function prepareForValidation($callable): static
    {
        $this->validationCallback = $callable;

        return $this;
    }

    /**
     * Indicates that the field value should be included in the JSON resource
     * when the user is not authorized to view the model/record
     */
    public function showValueWhenUnauthorizedToView(): static
    {
        $this->alwaysInJsonResource = true;

        return $this;
    }

    /**
     * Indicates whether to emit change event when value is changed
     */
    public function emitChangeEvent(?string $eventName = null): static
    {
        $this->emitChangeEvent = $eventName ?? 'field-'.$this->attribute.'-value-changed';

        return $this;
    }

    /**
     * Set field validation rules for all requests
     */
    public function rules(mixed $rules): static
    {
        $this->rules = array_merge(
            $this->rules,
            is_array($rules) ? $rules : func_get_args()
        );

        return $this;
    }

    /**
     * Set field validation rules that are only applied on create request
     */
    public function creationRules(mixed $rules): static
    {
        $this->creationRules = array_merge(
            $this->creationRules,
            is_array($rules) ? $rules : func_get_args()
        );

        return $this;
    }

    /**
     * Set field validation rules that are only applied on update request
     */
    public function updateRules(mixed $rules): static
    {
        $this->updateRules = array_merge(
            $this->updateRules,
            is_array($rules) ? $rules : func_get_args()
        );

        return $this;
    }

    /**
     * Set field validation rules for import
     */
    public function importRules(mixed $rules): static
    {
        $this->importRules = array_merge(
            $this->importRules,
            is_array($rules) ? $rules : func_get_args()
        );

        return $this;
    }

    /**
     * Get field validation rules for import
     */
    public function getImportRules(): array
    {
        $rules = [
            $this->requestAttribute() => $this->importRules,
        ];

        // we will remove the array rule in case found
        // because the import can handle arrays via coma separated values
        // for specific fields, other fields must implement their own logic
        return $this->sortRules(collect(array_merge_recursive(
            $this->getRules(),
            $rules
        ))->mapWithKeys(function ($rules, $attribute) {
            return [$attribute => collect($rules)->reject(fn ($rule) => $rule === 'array')->values()->all()];
        })->all());
    }

    /**
     * Get field validation rules for the request
     */
    public function getRules(): array
    {
        return $this->sortRules($this->createRulesArray($this->rules));
    }

    /**
     * Get the field validation rules for create request
     */
    public function getCreationRules(): array
    {
        $rules = $this->createRulesArray($this->creationRules);

        return $this->sortRules(array_merge_recursive(
            $this->getRules(),
            $rules
        ));
    }

    /**
     * Get the field validation rules for update request
     */
    public function getUpdateRules(): array
    {
        $rules = $this->createRulesArray($this->updateRules);

        return $this->sortRules(array_merge_recursive(
            $this->getRules(),
            $rules
        ));
    }

    /**
     * Sort the given rules in a proper format.
     */
    protected function sortRules(array $allRules)
    {
        $order = [
            'bail' => 1,
            'sometimes' => 2,
            'nullable' => 3,
            'required' => 3,
            StringRule::class => 5,
            // Default for any other rules
        ];

        foreach ($allRules as &$rules) {
            usort($rules, function ($a, $b) use ($order) {
                // Convert rule objects to class names or keep as string
                $aValue = is_object($a) ? get_class($a) : $a;
                $bValue = is_object($b) ? get_class($b) : $b;

                $aOrder = $order[$aValue] ?? 1000;
                $bOrder = $order[$bValue] ?? 1000;

                return $aOrder <=> $bOrder;
            });
        }

        return $allRules;
    }

    /**
     * Create rules ready array
     */
    protected function createRulesArray(array $rules): array
    {
        // If the array is not list, probably the user added array validation
        // rules e.q. '*.key' => 'required', in this case, we will make sure to include them
        if (! array_is_list($rules)) {
            $groups = collect($rules)->mapToGroups(function ($rules, $wildcard) {
                // If the $wildcard is integer, this means that it's a rule for the main field attribute
                // e.q. ['array', '*.key' => 'required']
                return [is_int($wildcard) ? 'attribute' : 'wildcard' => [$wildcard => $rules]];
            })->all();

            $merged = [];

            if (array_key_exists('attribute', $groups)) {
                $merged = array_merge($merged, [$this->requestAttribute() => $groups['attribute']?->flatten()->all()]);
            }

            if (array_key_exists('wildcard', $groups)) {
                $merged = array_merge($merged, $groups['wildcard']->mapWithKeys(function ($rules) {
                    $wildcard = array_key_first($rules);

                    return [$this->requestAttribute().'.'.$wildcard => Arr::wrap($rules[$wildcard])];
                })->all());
            }

            return $merged;
        }

        return [
            $this->requestAttribute() => $rules,
        ];
    }

    /**
     * Set field custom validation error messages
     */
    public function validationMessages(array $messages): static
    {
        $this->validationMessages = $messages;

        return $this;
    }

    /**
     * Get the field validation messages
     */
    public function prepareValidationMessages(): array
    {
        return collect($this->validationMessages)->mapWithKeys(function ($message, $rule) {
            return [$this->requestAttribute().'.'.$rule => $message];
        })->all();
    }

    /**
     * Set whether to exclude the field from Zapier response
     */
    public function excludeFromZapierResponse(): static
    {
        $this->excludeFromZapierResponse = true;

        return $this;
    }

    /**
     * Set the field custom field model
     */
    public function setCustomField(?CustomField $field): static
    {
        $this->customField = $field;

        return $this;
    }

    /**
     * Check whether the current field is a custom field
     */
    public function isCustomField(): bool
    {
        return ! is_null($this->customField);
    }

    /**
     * Get the field request attribute
     *
     * @return string
     */
    public function requestAttribute()
    {
        return $this->requestAttribute ?? $this->attribute;
    }

    /**
     * Hydrate the model value.
     */
    public function fill(Model $model, string $attribute, ResourceRequest $request, string $requestAttribute): ?callable
    {
        $value = $this->attributeFromRequest($request, $requestAttribute);

        if (is_callable($this->fillCallback)) {
            return call_user_func_array($this->fillCallback, [
                $model,
                $attribute,
                $request,
                $value,
                $requestAttribute,
            ]);
        }

        $model->{$attribute} = $value;

        return null;
    }

    /**
     * Add custom fill callback for the field.
     */
    public function fillUsing(callable $callback): static
    {
        $this->fillCallback = $callback;

        return $this;
    }

    /**
     * Get the field value for the given request
     */
    public function attributeFromRequest(ResourceRequest $request, string $requestAttribute): mixed
    {
        return $request->exists($requestAttribute) ? $request[$requestAttribute] : null;
    }

    /**
     * Check whether the field has options
     */
    public function isOptionable(): bool
    {
        return $this->isMultiOptionable() || $this instanceof Optionable;
    }

    /**
     * Check whether the field is multi optionable
     */
    public function isMultiOptionable(): bool
    {
        return $this instanceof MultiSelect || $this instanceof Checkbox;
    }

    /**
     * Mark the the field should be available only when there is an authenticated user.
     */
    public function authRequired(): static
    {
        $this->authRequired = true;

        return $this;
    }

    public function useSearchColumn(string|array|Expression $column)
    {
        $this->searchColumn = $column;

        return $this;
    }

    /**
     * Get the field search column.
     */
    public function searchColumn(): string|array|null
    {
        if ($this->searchable === false) {
            return null;
        }

        if ($this->searchColumn instanceof Expression) {
            return [$this->attribute => $this->searchColumn];
        }

        return $this->searchColumn ?? $this->attribute;
    }

    /**
     * Set if the field is searchable or not.
     */
    public function searchable(bool $value)
    {
        $this->searchable = $value;

        return $this;
    }

    /**
     * Prepare the field when it's intended to be used on the bulk edit action.
     */
    public function prepareForBulkEdit(): void
    {
        $this->rules = collect([...$this->rules, ...$this->updateRules])->unique()->all();
    }

    /**
     * Get the import data type.
     */
    public function importValueDataType(): string
    {
        return DataType::TYPE_STRING;
    }

    /**
     * Set the request for the field.
     */
    public static function setRequest(?ResourceRequest $request): void
    {
        static::$request = $request;
    }

    /**
     * Resolve the current request instance.
     *
     * @return \Illuminate\Http\Request
     */
    protected function resolveRequest()
    {
        if (static::$request) {
            return static::$request;
        }

        return app(ResourceRequest::class);
    }

    /**
     * Serialize for front end
     */
    public function jsonSerialize(): array
    {
        return array_merge([
            'component' => $this->component(),
            'formComponent' => $this->formComponent(),
            'detailComponent' => $this->detailComponent(),
            'indexComponent' => $this->indexComponent(),
            'inlineEditWith' => $this->inlineEditField(),
            'attribute' => $this->attribute,
            'label' => $this->label,
            'helpText' => $this->helpText,
            'helpTextDisplay' => $this->helpTextDisplay,
            'readonly' => $this->isReadOnly(),
            'collapsed' => $this->collapsed,
            'primary' => $this->isPrimary(),
            'showOnIndex' => $this->showOnIndex,
            'showOnCreation' => $this->showOnCreation,
            'showOnUpdate' => $this->showOnUpdate,
            'showOnDetail' => $this->showOnDetail,
            'applicableForIndex' => $this->isApplicableForIndex(),
            'applicableForCreation' => $this->isApplicableForCreation(),
            'applicableForUpdate' => $this->isApplicableForUpdate(),
            'toggleable' => $this->toggleable,
            'displayNone' => $this->displayNone,
            'emitChangeEvent' => $this->emitChangeEvent,
            'width' => $this->width,
            'value' => $this->defaultValue($this->resolveRequest()),
            'isRequired' => $this->isRequired($this->resolveRequest()),
            'isUnique' => $this->isUnique(),
            'canUnmarkUnique' => $this->canUnmarkUnique,
            'inlineEditPanelWidth' => $this->inlineEditPanelWidth,
            'hideLabel' => $this->hideLabel,
            'customField' => $this->isCustomField() ? new CustomFieldResource($this->customField) : null,
            'dateable' => $this instanceof Dateable,
        ], $this->meta());
    }
}
