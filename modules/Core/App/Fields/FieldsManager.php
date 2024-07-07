<?php
 

namespace Modules\Core\App\Fields;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Modules\Core\App\Contracts\Fields\Customfieldable;
use Modules\Core\App\Contracts\Fields\UniqueableCustomfield;
use Modules\Core\App\Facades\Fields;
use Modules\Core\App\Support\SubClassDiscovery;
use ReflectionClass;

class FieldsManager
{
    /**
     * Hold all the registered groups and fields.
     */
    protected static array $fields = [];

    /**
     * Loaded fields cache.
     */
    protected static array $loaded = [];

    /**
     * The files that are custom field able.
     */
    protected ?array $customFieldable = null;

    /**
     * Parsed custom fields cache.
     */
    protected ?Collection $customFields = null;

    /**
     * Register fields with group.
     */
    public function group(string $group, mixed $provider): static
    {
        static::flushLoadedCache();

        if (! isset(static::$fields[$group])) {
            static::$fields[$group] = [];
        }

        static::$fields[$group][] = $provider;

        return $this;
    }

    /**
     * Check whether the given group has fields registered.
     */
    public function has(string $group): bool
    {
        return $this->load($group)->isNotEmpty();
    }

    /**
     * Add fields to the given group.
     */
    public function add(string $group, mixed $provider): static
    {
        return $this->group($group, $provider);
    }

    /**
     * Replace the group fields with the given fields.
     */
    public function replace(string $group, mixed $provider): static
    {
        static::$fields[$group] = [];

        return $this->group($group, $provider);
    }

    /**
     * Get fields for the given group and view.
     */
    public function get(string $group, ?string $view = null): FieldsCollection
    {
        return $this->inGroup($group, $view);
    }

    /**
     * Get the fields intended for settings.
     */
    public function getForSettings(string $group, string $view): FieldsCollection
    {
        return $this->get($group, $view)
            ->reject(function (Field $field) use ($view) {
                return $field->isExcludedFromSettings($view);
            })
            ->filter(fn (Field $field) => match ($view) {
                Fields::DETAIL_VIEW => $field->isApplicableForDetail(),
                Fields::UPDATE_VIEW => $field->isApplicableForUpdate(),
                Fields::CREATE_VIEW => $field->isApplicableForCreation(),
            })
            ->values();
    }

    /**
     * Get all fields in specific group.
     */
    protected function inGroup(string $group, ?string $view = null): FieldsCollection
    {
        if (isset(static::$loaded[$cacheKey = (string) $group.$view])) {
            return static::$loaded[$cacheKey];
        }

        $callback = function (Field $field, int $index) use ($group, $view) {
            /**
             * Apply any custom attributes added by the user via settings
             */
            $field = $this->applyCustomizedAttributes($field, $group, $view);

            /**
             * Add field order if there is no customized order
             * This helps to sort them properly by default the way they are defined
             */
            $field->order ??= $index + 1;

            return $field;
        };

        return static::$loaded[$cacheKey] = $this->load($group)
            ->map($callback)
            ->sortBy('order')
            ->when(! Auth::check(), function ($fields) {
                return $fields->reject(function (Field $field) {
                    return $field->authRequired === true;
                });
            })->values();
    }

    /**
     * Save the customized fields.
     */
    public function customize(mixed $data, string $group, string $view): void
    {
        $this->syncSettingsToOppositeView($data, $group, $view);

        settings()->set(
            $this->storageKey($group, $view),
            json_encode($data)
        );

        settings()->save();

        static::flushLoadedCache();
    }

    /**
     * Get the customized fields.
     */
    public function customized(string $group, string $view, ?string $attribute = null): array
    {
        $attributes = json_decode(settings()->get($this->storageKey($group, $view), '[]'), true);

        if ($attribute) {
            return $attributes[$attribute] ?? [];
        }

        return $attributes;
    }

    protected function syncSettingsToOppositeView(mixed $data, string $group, string $view): void
    {
        // Technically, the details and the update views are the same, the details view option
        // exists only for the front-end, in this case, we need to make sure that the isRequired
        // customizable attribute should be propagated to the opposite view, as the fields for validation
        // when performing update are taken from the "update" view, as it's an update, there is no separate endpoints
        if (! in_array($view, [Fields::UPDATE_VIEW, Fields::DETAIL_VIEW])) {
            return;
        }

        $oppositeView = $view === Fields::UPDATE_VIEW ? Fields::DETAIL_VIEW : Fields::UPDATE_VIEW;

        $oppositeViewSettings = $this->customized($group, $oppositeView);

        foreach ($data as $attribute => $field) {
            if (! isset($oppositeViewSettings[$attribute])) {
                $oppositeViewSettings[$attribute] = [];
            }

            if (array_key_exists('isRequired', $field)) {
                $oppositeViewSettings[$attribute]['isRequired'] = $field['isRequired'];
            }

            if (array_key_exists('uniqueUnmarked', $field)) {
                $oppositeViewSettings[$attribute]['uniqueUnmarked'] = $field['uniqueUnmarked'];
            }

            if (count($oppositeViewSettings[$attribute]) === 0) {
                unset($oppositeViewSettings[$attribute]);
            }
        }

        if (count($oppositeViewSettings) > 0) {
            settings()->set(
                $this->storageKey($group, $oppositeView),
                json_encode($oppositeViewSettings)
            );
        }
    }

    /**
     * Purge the customized fields cache.
     */
    public static function flushLoadedCache(): void
    {
        static::$loaded = [];
    }

    /**
     * Purge the registered fields cache.
     */
    public static function flushRegisteredCache(): void
    {
        static::$fields = [];
    }

    /**
     * Flush the fields cache.
     */
    public static function flushCache(): void
    {
        static::flushLoadedCache();
        static::flushRegisteredCache();
    }

    /**
     * Get the available fields that can be used as custom fields.
     */
    public function customFieldable(): Collection
    {
        return $this->customFields ??= collect($this->scanCustomFieldables())->mapWithKeys(function (string $className) {
            /** @var \Modules\Core\App\Fields\Field */
            $field = (new ReflectionClass($className))->newInstanceWithoutConstructor();

            return [$type = class_basename($className) => [
                'type' => $type,
                'className' => $className,
                'uniqueable' => $field instanceof UniqueableCustomfield,
                'optionable' => $field->isOptionable(),
                'multioptionable' => $field->isMultiOptionable(),
            ]];
        });
    }

    /**
     * Custom fields that are custom field ables.
     */
    protected function scanCustomFieldables(): array
    {
        return $this->customFieldable ??= SubClassDiscovery::make(Customfieldable::class)
            ->in(__DIR__)
            ->moduleable()
            ->find();
    }

    /**
     * Get the multi optionable custom fields types.
     */
    public function getOptionableCustomFieldsTypes(): array
    {
        return $this->customFieldable()->where('optionable', true)->keys()->all();
    }

    /**
     * Get non optionable custom fields types.
     */
    public function getNonOptionableCustomFieldsTypes(): array
    {
        return array_diff($this->customFieldsTypes(), $this->getOptionableCustomFieldsTypes());
    }

    /**
     * Get the available custom fields types.
     */
    public function customFieldsTypes(): array
    {
        return $this->customFieldable()->keys()->all();
    }

    /**
     * Get the custom fields that can be marked as unique.
     */
    public function getUniqueableCustomFieldsTypes(): array
    {
        return $this->customFieldable()->where('uniqueable', true)->keys()->all();
    }

    /**
     * Loaded the provided group fields.
     */
    protected function load(string $group): FieldsCollection
    {
        $fields = new FieldsCollection;

        foreach (static::$fields[$group] ?? [] as $provider) {
            if ($provider instanceof Field) {
                $provider = [$provider];
            }

            if (is_array($provider)) {
                $fields = $fields->merge($provider);
            } elseif (is_callable($provider)) { // callable, closure, __invoke
                $fields = $fields->merge(call_user_func($provider));
            }
        }

        return $fields;
    }

    /**
     * Create customized storage key for settings.
     */
    protected function storageKey(string $group, string $view): string
    {
        return "fields-{$group}-{$view}";
    }

    /**
     * Get the allowed customize able attributes.
     */
    public function allowedCustomizableAttributes(): array
    {
        return [
            'order', 'showOnCreation', 'showOnUpdate',
            'showOnDetail', 'collapsed', 'isRequired', 'uniqueUnmarked',
        ];
    }

    /**
     * Get the allowed customize able attributes.
     */
    public function allowedCustomizableAttributesForPrimary(): array
    {
        return ['order'];
    }

    /**
     * Get the allowed customizable attributes for the given field.
     */
    protected function allowedCustomizeableAttributes(Field $field): array
    {
        // Protected the primary fields visibility and collapse options when direct API request
        // e.q. the field visibility is set to false when it must be visible because the field is marked as primary field

        return $field->isPrimary() ?
            $this->allowedCustomizableAttributesForPrimary() :
            $this->allowedCustomizableAttributes();
    }

    /**
     * Apply any customized options by user.
     */
    public function applyCustomizedAttributes(Field $field, string $group, ?string $view): Field
    {
        if (! $view || $field->isExcludedFromSettings($view)) {
            return $field;
        }

        $attributes = Arr::only(
            $this->customized($group, $view, $field->attribute),
            $this->allowedCustomizeableAttributes($field)
        );

        foreach (['order', 'showOnCreation', 'showOnUpdate', 'showOnDetail', 'collapsed'] as $key) {
            if (array_key_exists($key, $attributes)) {
                $field->{$key} = $attributes[$key];
            }
        }

        if (array_key_exists('uniqueUnmarked', $attributes) &&
            $field->isUnique() &&
            $field->canUnmarkUnique === true &&
            $attributes['uniqueUnmarked'] ?? null == true) {
            $field->notUnique();
        }

        if (array_key_exists('isRequired', $attributes) && $attributes['isRequired'] == true) {
            $field->rules(['sometimes', 'required'])->required(true);

            if (method_exists($field, 'withoutClearAction')) {
                $field->withoutClearAction();
            }
        }

        return $field;
    }
}
