<?php
 

namespace Modules\Contacts\App\Fields;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Contacts\App\Enums\PhoneType;
use Modules\Contacts\App\Http\Resources\PhoneResource;
use Modules\Contacts\App\Models\Phone as PhoneModel;
use Modules\Core\App\Common\Placeholders\GenericPlaceholder;
use Modules\Core\App\Fields\ChecksForDuplicates;
use Modules\Core\App\Fields\MorphMany;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Support\CountryCallingCode;
use Modules\Core\App\Table\MorphManyColumn;

class Phone extends MorphMany
{
    use ChecksForDuplicates;

    /**
     * Phone types
     */
    public array $types = [];

    /**
     * Default type
     */
    public ?PhoneType $type = null;

    /**
     * Field component.
     */
    public static $component = 'phone-field';

    /**
     * Indicates if the field is excluded from index query.
     */
    public bool $isExcludedFromIndexQuery = false;

    /**
     * Calling prefix
     *
     * @var mixed
     */
    public $callingPrefix = null;

    /**
     * Indicates whether the phone should be unique
     *
     * @var \Illuminate\Database\Eloquent\Model|bool
     */
    public $unique = false;

    /**
     * Indicates whether to skip the unique rule validation in import
     */
    public bool $uniqueRuleSkipOnImport = true;

    /**
     * Unique rule custom validation message
     *
     * @var string
     */
    public $uniqueRuleMessage;

    /**
     * The inline edit popover width (medium|large).
     */
    public string $inlineEditPanelWidth = 'large';

    protected static string $typeInNumberValueSeparator = '|';

    /**
     * Initialize new Phone instance class
     *
     * @param  string  $attribute  field attribute
     * @param  string|null  $label  field label
     */
    public function __construct($attribute, $label = null)
    {
        parent::__construct($attribute, $label);

        $this->types = collect(PhoneType::names())->mapWithKeys(function (string $name) {
            return [$name => __('contacts::fields.phone.types.'.$name)];
        })->all();

        $this->defaultType(PhoneType::mobile)
            ->rules([
                '*.number' => [function (string $attribute, mixed $number, Closure $fail, ResourceRequest $request) {
                    if (blank($number)) {
                        return;
                    }

                    if ($this->shouldPerformUniqueValidation($request) && ! $this->isNumberUnique($number, $request)) {
                        $fail($this->uniqueRuleMessage);
                    }

                    if ($this->requiresCallingPrefix() && ! CountryCallingCode::startsWithAny($number)) {
                        $fail('validation.calling_prefix')->translate(['attribute' => $this->label]);
                    }
                }, 'max:191'],
            ])
            ->prepareForValidation(function (mixed $value) {
                return $this->parsePreValidationValue($value);
            })->provideSampleValueUsing(function () {
                return [
                    [
                        'number' => PhoneModel::generateRandomNumber(),
                        'type' => array_rand($this->types),
                    ],
                ];
            })->provideImportValueSampleUsing(function () {
                return PhoneModel::generateRandomNumber().'|mobile'.','.PhoneModel::generateRandomNumber();
            })->fillUsing(function (Model $model, string $attribute, ResourceRequest $request, mixed $value) {
                return ! is_null($value) ? $this->fillCallback($value, $model) : null;
            })->resolveForJsonResourceUsing(function (Model $model, string $attribute) {
                if ($model->relationLoaded($this->morphManyRelationship)) {
                    return [
                        $attribute => PhoneResource::collection($this->resolve($model)),
                    ];
                }
            });
    }

    /**
     * Get the fill callback for the field.
     */
    protected function fillCallback(array $value, Model $model): Closure
    {
        return function () use ($value, $model) {
            $value = collect($value)->reject(fn ($attributes) => empty($attributes['number']))->values();

            if ($model->wasRecentlyCreated) {
                $model->phones()->createMany($value);
            } else {
                $this->performUpdateWithChangelog($model, $value);
            }
        };
    }

    /**
     * Perform update for the given phones.
     */
    protected function performUpdate(Collection $phones, Model $model): void
    {
        $original = clone $model->phones;

        $phones
            ->each(function ($attributes) use ($model) {
                $phone = $model->phones->where('number', $attributes['number'])->first();

                if ($phone) {
                    $phone->fill($attributes)->save();
                } else {
                    $phone = $model->phones()->create($attributes);
                    $model->setRelation('phones', $model->phones->push($phone));
                }
            })
            ->whenEmpty(
                function () use ($model) {
                    $model->phones->each(fn ($phone) => $this->performDelete($phone, $model));
                },
                function ($phones) use ($original, $model) {
                    // Filter the phone numbers that are not in the provided value to delete them
                    $original
                        ->filter(fn ($phone) => is_null($phones->where('number', $phone->number)->first()))
                        ->each(fn ($phone) => $this->performDelete($phone, $model));
                }
            );
    }

    /**
     * Delete the given phone for the given model.
     */
    protected function performDelete(PhoneModel $phone, Model $model): void
    {
        $phone->delete();
        $model->setRelation('phones', $model->phones->except($phone->id));
    }

    /**
     * Perform update with changelog.
     */
    protected function performUpdateWithChangelog(Model $model, Collection $values): void
    {
        if (! $model->relationLoaded('phones')) {
            $model->load(['phones', 'phones.phoneable']);
        }

        $before = $model->phones->pluck('number');

        $this->performUpdate($values, $model);

        $after = $model->phones->pluck('number');

        if ($before != $after) {
            $model->logDirtyAttributesOnLatestLog([
                'attributes' => ['phone' => $after->implode(', ')],
                'old' => ['phone' => $before->implode(', ')],
            ], $model);
        }
    }

    /**
     * Provide the column used for index
     */
    public function indexColumn(): MorphManyColumn
    {
        $column = new MorphManyColumn(
            $this->morphManyRelationship,
            'number',
            $this->label
        );

        $column->select('type')
            ->wrap()
            ->fillRowDataUsing(function (array &$row, Model $model) use ($column) {
                $row[$column->attribute] = $model->phones->map(
                    fn (PhoneModel $phone) => $column->toRowData($phone, ['type' => $phone->type->name])
                );
            });

        return $column;
    }

    /**
     * Mark the field as unique
     *
     * @param  string  $model
     * @param  string  $message
     */
    public function unique($model, $message = 'The phone number already exists', $skipOnImport = true): static
    {
        $this->unique = $model;
        $this->uniqueRuleMessage = $message;
        $this->uniqueRuleSkipOnImport = $skipOnImport;

        return $this;
    }

    /**
     * Set the default phone type
     */
    public function defaultType(PhoneType $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Determine if the field is using a prefix from a country directly.
     * In this case, the prefix is provided as INT from a country.
     */
    protected function isUsingPrefixFromCountry(): bool
    {
        $prefix = $this->callingPrefix;

        // Default value via closure?
        if ($prefix instanceof Closure) {
            $prefix = $prefix($this->resolveRequest());
        }

        return is_int($prefix);
    }

    /**
     * Get the phone field calling prefix
     */
    public function callingPrefix(): string|bool|null
    {
        $prefix = $this->callingPrefix;

        // Default value via closure?
        if ($prefix instanceof Closure) {
            $prefix = $prefix($this->resolveRequest());
        }

        // Provided prefix via country ID to take the calling prefix from?
        if (is_int($prefix)) {
            $prefix = CountryCallingCode::fromCountry($prefix);
        }

        // The default calling prefix provided does not start with the + char?
        if (! empty($prefix) && is_string($prefix) && ! Str::startsWith($prefix, '+')) {
            $prefix = '+'.$prefix;
        }

        return $prefix;
    }

    /**
     * Add calling prefix
     */
    public function requireCallingPrefix(bool|int|Closure|string|null $default = true): static
    {
        $this->callingPrefix = $default;

        return $this;
    }

    /**
     * Check if the number requires calling prefix
     */
    public function requiresCallingPrefix(): bool
    {
        return ! is_null($this->callingPrefix());
    }

    /**
     * Check whether the given number is unique.
     */
    protected function isNumberUnique(string $number, ResourceRequest $request): bool
    {
        $query = PhoneModel::query();

        if ($request->isUpdateRequest()) {
            $query->whereNot('phoneable_id', $request->record()->getKey());
        }

        return $query->where('number', $number)
            ->where('phoneable_type', $this->unique)
            ->count() === 0;
    }

    /**
     * If needed, add calling prefix to the given phone
     */
    protected function addCallingPrefixIfNeeded(array $phone): array
    {
        $prefix = $this->callingPrefix();

        if (
            ! $prefix ||
            (empty($phone['number']) || CountryCallingCode::startsWithAny($phone['number']))
        ) {
            return $phone;
        }

        // When the field is using a prefix from country, it's safe
        // to check if the number starts with the actual prefix (without the +)
        // if yes, we will only add the + sign
        // for example phone provided during import 1235323456 and country US
        // in this case, if we add the prefix in full, the phone will be +1 1235323456
        // but now will be +1235323456
        if ($this->isUsingPrefixFromCountry()) {
            $plainPrefix = ltrim($prefix, '+');

            if (str_starts_with($phone['number'], $plainPrefix)) {
                $phone['number'] = '+'.$phone['number'];
            }
        }

        if (! str_starts_with($phone['number'], $prefix)) {
            $phone['number'] = $prefix.$phone['number'];
        }

        return $phone;
    }

    /**
     * Mark the field as not unique
     */
    public function notUnique(): static
    {
        // Perform reset
        $this->unique = false;
        $this->uniqueRuleSkipOnImport = true;
        $this->uniqueRuleMessage = null;

        return $this;
    }

    /**
     * Check whether the field is unique
     */
    public function isUnique(): bool
    {
        return (bool) $this->unique;
    }

    /**
     * Check whether the unique validation should be performed.
     */
    protected function shouldPerformUniqueValidation(ResourceRequest $request): bool
    {
        if ($request->isImportRequest() && $this->uniqueRuleSkipOnImport) {
            return false;
        }

        return (bool) $this->unique;
    }

    /**
     * Ensure that the provided phone value is in proper format.
     */
    protected function parsePreValidationValue(string|array|null $value): array
    {
        // Early fail
        if (is_null($value)) {
            return [];
        }

        // Allow providing the phone number as string, used on import, API, Zapier etc...
        // Possible values: "+55282855292929" "+55282855292929|work" "+55282855292929,+123123558922|other"
        // Note that when the phone type is not provided, will use the default type
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        return collect($value)
            ->map(function (array|string $phone) {
                // Allow providing the phone only e.q. ['+55282855292929', '+123123558922', '+123123558922|work']
                return ! is_array($phone) ? ['number' => trim($phone)] : $phone;
            })->map(function (array $phone) {
                // Allow providing the type via the phone, separated by pipe e.q. +55282855292929|work
                $this->extractTypeFromNumber(static::$typeInNumberValueSeparator, $phone);

                // Add the phone type when type is provided as string
                if (isset($phone['type']) && ! $phone['type'] instanceof PhoneType) {
                    $phone['type'] = PhoneType::find($phone['type']) ?? $this->type;
                }

                return $phone;
            })->all();
    }

    /**
     * After validation callback has passed for all fields.
     *
     * The phone field may depends on country ID which may not be in a properly parsed
     * ID before the phone field validation callbacks run, in this case, we will
     * set the calling prefix after all fields validation callbacks are finished.
     */
    public function afterValidationCallback($value, ResourceRequest $request): void
    {
        $request[$this->requestAttribute()] = collect($value)->map(
            fn (array $phone) => $this->addCallingPrefixIfNeeded($phone)
        )->all();
    }

    /**
     * Allow user to provide type in the number via the provided separator
     */
    protected function extractTypeFromNumber(string $separator, array &$phone): void
    {
        $number = $phone['number'];

        if ($number && str_contains($number, $separator)) {
            if ($type = PhoneType::find(strtolower(Str::afterLast($number, $separator)))) {
                $phone['type'] = $type;
                $phone['number'] = Str::beforeLast($number, $separator);
            }
        }
    }

    /**
     * Resolve the displayable field value
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return string|null
     */
    public function resolveForDisplay($model)
    {
        $value = $this->resolve($model);

        if ($value->isNotEmpty()) {
            return $value->pluck('number')->implode(', ');
        }
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
     * Resolve the field value for export
     *
     * @param  \Modules\Core\App\Models\Model  $model
     * @return string|null
     */
    public function resolveForExport($model)
    {
        if (is_callable($this->exportCallback)) {
            return call_user_func_array($this->exportCallback, [$model, $this->resolve($model), $this->attribute]);
        }

        return (string) $this->resolveForDisplay($model);
    }

    /**
     * Get the field search column.
     */
    public function searchColumn(): string
    {
        return 'phones.number';
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'types' => $this->types,
            'type' => $this->type?->name,
            'callingPrefix' => value(function () {
                $prefix = $this->callingPrefix();

                return $prefix === true ? null : $prefix;
            }),
        ]);
    }
}
