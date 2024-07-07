<?php
 

namespace Modules\Core\App\Fields;

use Closure;
use Illuminate\Validation\Validator;
use Modules\Core\App\Contracts\Fields\Customfieldable;
use Modules\Core\App\Contracts\Fields\Dateable;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Filters\Filter;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Models\CustomField;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Rules\StringRule;
use Modules\Core\App\Table\BelongsToColumn;
use Modules\Core\App\Table\Column;
use Modules\Core\App\Table\MorphToManyColumn;

class CustomFieldFactory
{
    /**
     * The optionable custom field option id key.
     */
    protected static string $optionId = 'id';

    /**
     * The optionable custom field option label key.
     */
    protected static string $optionLabel = 'name';

    /**
     * Filters namespace.
     */
    protected static string $filterNamespace = 'Modules\Core\App\Filters';

    /**
     * Fields namespace.
     */
    protected static string $fieldNamespace = __NAMESPACE__;

    /**
     * The field types that are excluded from filters.
     */
    protected static array $withoutFiltering = ['Textarea', 'ColorSwatch'];

    /**
     * Create new CustomFieldFactory instance.
     */
    public function __construct(protected string $resourceName)
    {
    }

    /**
     * Create filters from the custom fields.
     */
    public function createFiltersFromFields(): array
    {
        $filters = [];

        foreach ($this->fields() as $field) {
            $filters[] = $this->createFilterInstance($field->field_type, $field);
        }

        return array_filter($filters);
    }

    /**
     * Create field class from the given custom field.
     */
    public static function createInstance(CustomField $field): Field
    {
        $instance = static::createFieldInstance(static::$fieldNamespace, $field);

        $rules = [];

        // By default the custom fields are hidden on "index" view.
        $instance->tapIndexColumn(fn (Column $column) => $column->hidden(true));

        if ($instance->isMultiOptionable()) {
            $instance
                ->eachOnNewLine()
                ->fillUsing(static::multiOptionableFillCallback($instance))
                ->tapIndexColumn(fn (Column $column) => $column->wrap())
                ->searchable(false)
                ->resolveUsing($field->prepareRelatedOptions(...));
        } elseif ($instance->isOptionable()) {
            $instance
                ->fillUsing(static::optionableFillCallback($instance))
                // allow request usage e.q.: search_fields=cf_custom_field_select
                // instead of: search_fields=relationName.name
                ->useSearchColumn([
                    $field->field_id => ['column' => $field->relationName.'.'.static::$optionLabel],
                ]);
        } elseif ($instance instanceof Text) {
            $rules = [...$rules, 'nullable', StringRule::make()];
        } elseif ($instance instanceof Url) {
            $rules = [...$rules, 'nullable', StringRule::make(), 'url'];
        } elseif ($instance instanceof Numeric) {
            $rules = [...$rules, 'nullable', 'numeric', 'decimal:0,3', 'min:0'];
        } elseif ($instance instanceof Email || $instance instanceof Number) {
            $rules = [...$rules, 'nullable'];
        } elseif ($instance instanceof Dateable) {
            $instance->withMeta(['attributes' => [
                'clearable' => ! $instance->isRequired(app(ResourceRequest::class)),
            ]]);
        }

        if ($field->is_unique) {
            $instance->unique($field->resource()->model());
        }

        return $instance->rules($rules)->setCustomField($field);
    }

    /**
     * Get the optionable field fill callback.
     *
     * @return Closure(Model $model, string $attribute, ResourceRequest $request, mixed $value, string $requestAttribute): void
     */
    protected static function optionableFillCallback(Field&Optionable&Customfieldable $field)
    {
        return function (Model $model,
            string $attribute,
            ResourceRequest $request,
            mixed $value,
            string $requestAttribute
        ) {
            if (! is_null($value)) {
                $model->{$attribute} = $value;
            }
        };
    }

    /**
     * Get the multi optionable field fill callback.
     *
     * @return Closure(Model $model, string $attribute, ResourceRequest $request, mixed $value, string $requestAttribute): void
     */
    protected static function multiOptionableFillCallback(Field&Optionable&Customfieldable $field): Closure
    {
        return function (Model $model,
            string $attribute,
            ResourceRequest $request,
            mixed $value,
            string $requestAttribute
        ) use ($field) {
            return function () use ($model, $value, $field) {
                if (! is_null($value)) {
                    (new CustomFieldService)->syncOptionsForModel(
                        $model,
                        $field,
                        $value,
                        $model->wasRecentlyCreated ? 'create' : 'update'
                    );
                }
            };
        };
    }

    /**
     * Configure optionable field.
     */
    protected static function configureOptionableField(CustomField $customField, Field&Optionable&Customfieldable $field): void
    {
        $field
            ->acceptLabelAsValue()
            ->prepareForValidation(function (mixed $value, ResourceRequest $request, Validator $validator) use ($field) {
                return static::parseOptionableFieldPreValidationValue($value, $request, $validator, $field);
            })
            ->tapIndexColumn(fn (Column $column) => $column->select('swatch_color'))
            ->displayUsing(function ($model) use ($customField, $field) {
                return $field->isMultiOptionable() ?
                    $model->{$customField->relationName}->pluck(static::$optionLabel)->implode(', ') :
                    $customField->options->find($model->{$customField->field_id})->{static::$optionLabel} ?? '';
            })
            ->displayAsBadges()
            ->swapIndexColumn(
                fn () => $field->isMultiOptionable() ?
                static::createColumnWhenMultiOptionable($customField) :
                static::createColumnWhenSingleOptionable($customField)
            );
    }

    /**
     * Create new field class instance.
     *
     * @return (\Modules\Core\App\Fields\Field&\Modules\Core\App\Contracts\Fields\Customfieldable)|\Modules\Core\App\Fields\Optionable|\Modules\Core\App\Filters\Filter
     */
    protected static function createFieldInstance(
        string $namespace,
        CustomField $field,
        ?string $type = null,
    ): Field|Filter {
        $class = '\\'.$namespace.'\\'.($type ?? $field->field_type);

        /** @var (\Modules\Core\App\Fields\Field&\Modules\Core\App\Contracts\Fields\Customfieldable)|\Modules\Core\App\Fields\Optionable|\Modules\Core\App\Filters\Filter */
        $instance = (new $class($field->field_id, $field->label));

        if ($instance->isOptionable()) {
            $instance->valueKey(static::$optionId)->labelKey(static::$optionLabel)->options($field->prepareOptions());

            if ($instance instanceof Field) {
                static::configureOptionableField($field, $instance);
            }
        }

        return $instance;
    }

    /**
     * Create filter instance from the given custom field.
     */
    protected function createFilterInstance(string $type, CustomField $field): ?Filter
    {
        if (in_array($type, static::$withoutFiltering)) {
            return null;
        }

        if ($type === 'Email') {
            $type = 'Text';
        } elseif ($type === 'Boolean') {
            $type = 'Radio';
        }

        $instance = static::createFieldInstance(static::$filterNamespace, $field, $type);

        if ($field->isMultiOptionable()) {
            $instance->query($this->multiOptionFilterQuery($field));
        } elseif ($field->field_type === 'Boolean') {
            $instance->options([true => __('core::app.yes'), false => __('core::app.no')]);
        }

        return $instance;
    }

    /**
     * Create table column when fields is multi optionable field.
     */
    protected static function createColumnWhenMultiOptionable(CustomField $field): MorphToManyColumn
    {
        return (new MorphToManyColumn(
            $field->relationName,
            static::$optionLabel,
            $field->label,
            $field->field_id
        ))->appends('swatch_color');
    }

    /**
     * Create table column when field is single optionable field.
     */
    protected static function createColumnWhenSingleOptionable(CustomField $field): BelongsToColumn
    {
        return (new BelongsToColumn(
            $field->relationName,
            static::$optionLabel,
            $field->label, $field->field_id
        ))->appends('swatch_color');

    }

    /**
     * Create multi option filter query.
     */
    protected function multiOptionFilterQuery(CustomField $field): callable
    {
        return function ($builder, $value, $condition, $sqlOperator) use ($field) {
            $method = strtolower($sqlOperator['operator']) === 'in' ? 'whereHas' : 'whereDoesntHave';

            return $builder->{$method}($field->relationName, function ($query) use ($value) {
                return $query->whereIn('id', $value);
            });
        };
    }

    /**
     * Get the resource custom fields.
     */
    protected function fields(): CustomFieldCollection
    {
        return Innoclapps::resourceByName($this->resourceName)->customFields();
    }

    /**
     * Handle the label option when custom field is optionable.
     */
    protected static function parseOptionableFieldPreValidationValue(
        mixed $value,
        ResourceRequest $request,
        Validator $validator,
        Field&Customfieldable&Optionable $field): mixed
    {
        if (is_null($value)) {
            return $value;
        }

        if ($field->isMultiOptionable()) {
            [$valid, $invalid] = $field->parseValueAsLabelViaMultiOptionable($value);

            if (count($invalid) > 0) {
                $validator->after(function (Validator $validator) use ($request, $field, $invalid) {
                    if ($validator->errors()->isEmpty()) {
                        foreach ($invalid as $label) {
                            $optionId = static::createOption($label, $field);

                            $request->merge([
                                $field->requestAttribute() => [...$request->input($field->requestAttribute()), ...[$optionId]],
                            ]);
                        }
                    }
                });
            }

            return $valid;
        } else {
            if ($option = $field->optionByLabel($value)) {
                $value = $field->getKeyFromOption($option);
            } elseif (! $field->optionByKey($value)) {
                // Provided option is key and does not exists, create new.
                $validator->after(function (Validator $validator) use ($value, $request, $field) {
                    if ($validator->errors()->isEmpty()) {
                        $optionId = static::createOption($value, $field);
                        $request->merge([$field->requestAttribute() => $optionId]);
                    }
                });
            }

            return $value;
        }
    }

    /**
     * Create new optionable custom field option.
     */
    protected static function createOption(string|int $label, Field&Optionable&Customfieldable $field): int
    {
        $customField = app(CustomFieldService::class)->createOptions([
            static::$optionLabel => $label,
        ], $field->customField);

        $options = $customField->options()->get();
        $id = $options->firstWhere(static::$optionLabel, $label)->getKey();

        $field->setCustomField($customField)
            ->clearCachedOptions()
            ->options($customField->prepareOptions($options));

        return (int) $id;
    }
}
