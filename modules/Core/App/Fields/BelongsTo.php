<?php
 

namespace Modules\Core\App\Fields;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Models\Model as CoreModel;
use Modules\Core\App\Table\BelongsToColumn;

class BelongsTo extends Optionable
{
    use Selectable;

    /**
     * From where the label key should be taken
     */
    public string $labelKey = 'name';

    /**
     * Can be used to connect multiple fields
     *
     * @var null|\Modules\Core\App\Fields\BelongsTo
     */
    public $dependsOn;

    /**
     * The relation name related to $dependsOn
     */
    public ?string $dependsOnRelationship = null;

    /**
     * Field component.
     */
    public static $component = 'belongs-to-field';

    /**
     * Field relationship name
     */
    public string $belongsToRelation;

    /**
     * Field JSON Resource
     */
    protected ?string $jsonResource = null;

    /**
     * The related model
     *
     * @var \Modules\Core\App\Models\Model
     */
    protected $model;

    /**
     * Indicates whether new record will be created when the field accepts label as value
     * in case the provided value does not exists in the database
     */
    protected bool $createRecordIfLabelIsMissing = true;

    /**
     * Create new instance of BelongsTo field
     *
     * @param  string  $name
     * @param  \Modules\Core\App\Models\Model|string  $model
     * @param  string  $label
     */
    public function __construct($name, $model, $label = null, $attribute = null)
    {
        $this->model = ! $model instanceof EloquentModel ? new $model : $model;

        parent::__construct($attribute ?? $this->model->getForeignKey(), $label);

        $this->belongsToRelation = $name;

        $this
            ->valueKey($this->model->getKeyName())
            ->displayUsing(function ($model) {
                return $model->{$this->belongsToRelation}->{$this->labelKey} ?? null;
            });
    }

    /**
     * Set the JSON resource class for the BelongsTo relation.
     */
    public function setJsonResource(?string $resourceClass): static
    {
        $this->jsonResource = $resourceClass;

        return $this;
    }

    /**
     * Get the related model.
     */
    public function getModel(): EloquentModel|CoreModel
    {
        return $this->model;
    }

    /**
     * Provide the column used for index.
     */
    public function indexColumn(): BelongsToColumn
    {
        return new BelongsToColumn($this->belongsToRelation, $this->labelKey, $this->label);
    }

    /**
     * Connect the fields with another fields
     * e.q. can be used in imports to determine e.q. the proper stage for the pipeline
     */
    public function dependsOn(BelongsTo $field, string $relation): static
    {
        $this->dependsOn = $field;
        $this->dependsOnRelationship = $relation;

        return $this;
    }

    /**
     * Provides the BelongsTo instance options.
     */
    public function resolveOptions(): array
    {
        $options = parent::resolveOptions();

        if (count($options) === 0 && ! $this->isAsync()) {
            $options = $this->model->newQuery()
                ->select([$this->labelKey, $this->valueKey])
                ->orderBy($this->labelKey)
                ->get()
                ->all();
        }

        return $options;
    }

    /**
     * Get the sample value for the field.
     */
    public function sampleValue(): mixed
    {
        if ($this->dependsOn) {
            $dependent = $this->dependsOn->getModel()
                ->first()
                ->{$this->dependsOnRelationship}()
                ->first();

            return $dependent->{$this->acceptLabelAsValue ? $this->labelKey : $this->valueKey};
        }

        return parent::sampleValue();
    }

    /**
     * Resolve the value for JSON Resource.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return array
     */
    public function resolveForJsonResource($model)
    {
        if (is_callable($this->jsonResourceCallback)) {
            return call_user_func_array($this->jsonResourceCallback, [$model, $this->attribute]);
        }

        $attributes = [
            $this->attribute => $this->resolve($model),
            $this->belongsToRelation => null,
        ];

        if ($model->relationLoaded($this->belongsToRelation) && $related = $model->getRelation($this->belongsToRelation)) {
            if ($this->jsonResource) {
                $resource = $this->jsonResource;
                $attributes[$this->belongsToRelation] = new $resource($related);
            } else {
                $attributes[$this->belongsToRelation] = [
                    $this->valueKey => $related->getAttribute($this->valueKey),
                    $this->labelKey => $related->getAttribute($this->labelKey),
                ];
            }
        }

        return $attributes;
    }

    /**
     * Get the mailable template placeholder.
     *
     * @param  \Modules\Core\App\Models\Model|null  $model
     * @return \Modules\Core\App\Common\Placeholders\Placeholder
     */
    public function mailableTemplatePlaceholder($model)
    {
        return parent::mailableTemplatePlaceholder($model)->tag(
            Str::snake(Str::replaceLast('_id', '', $this->belongsToRelation))
        );
    }

    /**
     * Accept string value.
     */
    public function acceptLabelAsValue(bool $createIfMissing = true): static
    {
        $this->createRecordIfLabelIsMissing = $createIfMissing;
        $this->acceptLabelAsValue = true;

        $this->prepareForValidation(function (mixed $value, ResourceRequest $request, Validator $validator) {
            if (is_null($value)) {
                return $value;
            }

            // TODO: This will fail with async select
            if ($option = $this->optionByKeyOrLabel($value)) {
                $value = $this->getKeyFromOption($option);
            } elseif ($optionFromNonFiltered = $this->optionByKeyOrLabelFromNonFilteredOptions($value)) {
                if ($id = $this->handleNonAuthorizedOptionProvided($optionFromNonFiltered, $request, $validator)) {
                    $value = $id;
                }
            } elseif ($this->createRecordIfLabelIsMissing) {
                $this->createNewOptionAfterValidationPasses($value, $request, $validator);
            } else {
                $this->addInvalidOptionValidationError($validator);
            }

            return $value;
        });

        return $this;
    }

    /**
     * Get the field search column.
     */
    public function searchColumn(): array
    {
        return [
            $this->belongsToRelation.'.'.$this->labelKey,
            $this->attribute => '=',
        ];
    }

    /**
     * Handle when user provides non authorized options.
     */
    protected function handleNonAuthorizedOptionProvided(array|object $option, ResourceRequest $request, Validator $validator): mixed
    {
        // The user has provided foreign ID from the non-filtered options
        // in this case, when update request, we will check if the value matches the current record value
        // if yes, we will leave it as it is, otherwise, will fail
        // as the user is not authorized to provide the value as the option does not exists in the
        // the front-end options collection but exists in the non filtered options.
        $foreignId = $this->getKeyFromOption($option);

        if ($request->isUpdateRequest() && $request->record()->getAttribute($this->attribute) == $foreignId) {
            return $foreignId;
        } else {
            $this->addInvalidOptionValidationError($validator);
        }

        return null;
    }

    /**
     * Create new option after validation passes.
     *
     * @param  string  $label
     */
    protected function createNewOptionAfterValidationPasses($label, ResourceRequest $request, Validator $validator)
    {
        $validator->after(function (Validator $validator) use ($request, $label) {
            if ($validator->errors()->isEmpty()) {
                $this->createNewOption($label, $request);
            }
        });
    }

    /**
     * Create new option in storage.
     *
     * @param  string  $label
     */
    protected function createNewOption($label, ResourceRequest $request): void
    {
        if ($option = $this->model->create($this->attributesForNewOption($label, $request))) {
            $request->merge([$this->requestAttribute() => $option->getKey()]);
            // Clear the cached options collection as next rows may
            // contain the same option and in this case, because the collection
            // was cached, the newly created option won't be available
            $this->clearCachedOptions();
        }
    }

    /**
     * Get the attributes when creating a new option.
     */
    protected function attributesForNewOption(string $label, ResourceRequest $request): array
    {
        return array_filter(array_merge([
            $this->labelKey => $label,
            $this->dependsOn ? [$this->dependsOn->attribute => $request->input($this->dependsOn->attribute)] : null,
        ]));
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'belongsToRelation' => $this->belongsToRelation,
            'dependsOn' => $this->dependsOn ? $this->dependsOn->attribute : null,
        ]);
    }
}
