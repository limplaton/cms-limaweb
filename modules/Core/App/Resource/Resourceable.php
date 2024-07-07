<?php
 

namespace Modules\Core\App\Resource;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Cache;
use Modules\Core\App\Contracts\Fields\Deleteable;
use Modules\Core\App\Contracts\Resources\AcceptsCustomFields;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Facades\Zapier;
use Modules\Core\App\Fields\CustomFieldCollection;
use Modules\Core\App\Models\CustomField;
use Modules\Core\App\Models\CustomFieldOption;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Resource\Events\ResourceRecordDeleted;
use Modules\Core\App\Resource\Query\EloquentQueryBuilder;

/** @mixin \Modules\Core\App\Models\Model */
trait Resourceable
{
    public static array $beforeSyncCustomFieldOptions = [];

    public static array $afterSyncCustomFieldOptions = [];

    /**
     * Boot the resource model
     */
    protected static function bootResourceable(): void
    {
        if (! Innoclapps::isAppInstalled() || ! $resource = static::resource()) {
            return;
        }

        static::deleted(function (Model $model) {
            ResourceRecordDeleted::dispatch($model, $model::resource());

            static::resource()
                ->getFields()
                ->whereInstanceOf(Deleteable::class)
                ->each(function (Deleteable $field) use ($model) {
                    $field->delete($model);
                });
        });

        if ($resource instanceof AcceptsCustomFields) {
            static::bootCustomFields();
        }

        if ($resource::$hasZapierHooks === true) {
            static::bootZapierHooks();
        }
    }

    /**
     * Get the model related resource instance.
     */
    public static function resource(): ?Resource
    {
        return Innoclapps::resourceByModel(static::class);
    }

    /**
     * Boot the resource Zapier hooks.
     */
    protected static function bootZapierHooks(): void
    {
        foreach (Zapier::modelEvents() as $event) {
            static::{$event}(function ($model) use ($event) {
                Zapier::queue($event, $model->getKey(), static::resource());
            });
        }
    }

    /**
     * Boot the related model resource custom fields.
     */
    protected static function bootCustomFields(): void
    {
        static::bootCustomFieldsWithOptions();
    }

    /**
     * Boot the model related resource custom fields with options.
     */
    protected static function bootCustomFieldsWithOptions(): void
    {
        static::deleting(function (Model $model) {
            if ($model->isReallyDeleting()) {
                foreach (static::getCustomFields()->multiOptionable() as $field) {
                    $model->{$field->relationName}()->detach();
                }
            }
        });
    }

    /**
     * Get the model related resource custom fields.
     */
    public static function getCustomFields(): CustomFieldCollection
    {
        if (! static::resource()) {
            return new CustomFieldCollection([]);
        }

        return static::resource()->customFields();
    }

    /**
     * Create new custom field multi value options relation.
     */
    public function newMultiValueOptionCustomFieldRelation(CustomField $field): MorphToMany
    {
        $instance = $this->newRelatedInstance(CustomFieldOption::class);

        return $this->newMorphToMany(
            $instance->newQuery(),
            $this,
            'model',
            'model_has_custom_field_options',
            'model_id',
            'option_id',
            $this->getKeyName(),
            $instance->getKeyName(),
            $field->relationName,
            false
        )->wherePivot('custom_field_id', $field->id);
    }

    /**
     * Create new custom field single value options relation.
     */
    public function newSingleValueOptionCustomFieldRelation(CustomField $field): BelongsTo
    {
        $instance = $this->newRelatedInstance(CustomFieldOption::class);

        return $this->newBelongsTo(
            $instance->newQuery(),
            $this,
            $field->field_id,
            $instance->getKeyName(),
            $field->relationName
        );
    }

    /**
     * Create new custom field relation.
     */
    public function newCustomFieldRelation(CustomField $field)
    {
        if (! $field->isMultiOptionable()) {
            return $this->newSingleValueOptionCustomFieldRelation($field);
        }

        return $this->newMultiValueOptionCustomFieldRelation($field);
    }

    /**
     * Register event for when before sync custom field options.
     */
    public static function beforeSyncCustomFieldOptions(Closure $callback)
    {
        static::$beforeSyncCustomFieldOptions[static::class][spl_object_hash($callback)] = $callback;
    }

    /**
     * Register event for when after sync custom field options.
     */
    public static function afterSyncCustomFieldOptions(Closure $callback)
    {
        static::$afterSyncCustomFieldOptions[static::class][spl_object_hash($callback)] = $callback;
    }

    /**
     * Get the resource model associateable relations.
     */
    public function associateableRelations(): array
    {
        return static::resource()->associateableRelations();
    }

    /**
     * Load the resource model associations.
     */
    public function loadAssociations(): static
    {
        return $this->load($this->associateableRelations());
    }

    /**
     * Scope a query to eager load the resource associations.
     */
    public function scopeWithAssociations(Builder $query): void
    {
        $query->with($this->associateableRelations());
    }

    /**
     * Scope a query to eager count the resource associations.
     */
    public function scopeWithCountAssociations(Builder $query): void
    {
        $query->withCount($this->associateableRelations());
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new EloquentQueryBuilder($query);
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @return static
     *
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     */
    public function fill(array $attributes)
    {
        // Because model may be initialized without attributes in this case
        // first, we will check if there are attributes, then will merge the non-relation
        // custom field field_id's as fillable attributes
        if (static::resource() instanceof AcceptsCustomFields &&
                count($attributes) > 0 &&
                ! static::isUnguarded() &&
                count($this->getFillable()) > 0) {
            $this->fillable(array_unique(
                array_merge(
                    $this->getFillable(),
                    $this->getFillableCustomFieldsAttributes()
                )
            ));
        }

        return parent::fill($attributes);
    }

    /**
     * Get the casts array.
     *
     * @todo In Laravel 11, use the casts method.
     *
     * @return array
     */
    public function getCasts()
    {
        return array_merge(parent::getCasts(), $this->getCustomFieldsCasts());
    }

    /**
     * Get the dynamic relation resolver if defined or inherited, or return null.
     *
     * @param  string  $class
     * @param  string  $key
     * @return mixed
     */
    public function relationResolver($class, $key)
    {
        $field = $this->getOptionableCustomFields()->firstWhere('relationName', $key);

        if ($field) {
            return function (self $model) use ($field) {
                return $model->newCustomFieldRelation($field);
            };
        }

        return parent::relationResolver($class, $key);
    }

    /**
     * Get the resource custom fields fillable attributes.
     */
    protected function getFillableCustomFieldsAttributes(): array
    {
        return Cache::store('array')->rememberForever(static::class.'-fillable-cf', function () {
            return static::getCustomFields()->fillable();
        });
    }

    /**
     * Get the resource custom fields casts.
     */
    protected function getCustomFieldsCasts(): array
    {
        return Cache::store('array')->rememberForever(static::class.'-cf-casts', function () {
            return static::getCustomFields()->modelCasts();
        });
    }

    /**
     * Get the resource optionable custom fields.
     */
    protected function getOptionableCustomFields(): CustomFieldCollection
    {
        return Cache::store('array')->rememberForever(static::class.'-cf-optionable', function () {
            return static::getCustomFields()->optionable();
        });
    }
}
