<?php
 

namespace Modules\Core\App\Fields;

use ArrayAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Http\Resources\TagResource;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Models\Tag;
use Modules\Core\App\Table\MorphToManyColumn;

class Tags extends Field
{
    /**
     * The type the tags are intended for.
     */
    protected ?string $type = null;

    protected ?Collection $tags = null;

    /**
     * Indicates if the field is searchable.
     */
    protected bool $searchable = false;

    /**
     * Field component.
     */
    public static $component = 'tags-field';

    /**
     * The inline edit popover width (medium|large).
     */
    public string $inlineEditPanelWidth = 'large';

    /**
     * Additional relationships to eager load when quering the resource.
     */
    public array $with = ['tags'];

    /**
     * Initialize new Tags instance.
     */
    public function __construct($attribute = 'tags', $label = null)
    {
        parent::__construct($attribute, $label ?? __('core::tags.tags'));

        $this->withDefaultValue([])
            ->provideSampleValueUsing(function () {
                $availableTags = $this->getTags();

                if ($availableTags->isEmpty()) {
                    return 'Tag 1, Tag 2';
                }

                return $availableTags->take(2)->pluck('name')->implode(', ');
            })
            ->prepareForValidation(function (mixed $value) {
                if (is_string($value)) {
                    $value = Str::of($value)
                        ->explode(',')
                        ->filter()
                        ->trim()
                        ->all();
                }

                return $value;
            })
            ->displayUsing(fn ($model, $value) => $value->pluck('name')->implode(', '))
            ->fillUsing(function (Model $model, string $attribute, ResourceRequest $request, mixed $value) {
                return function () use ($value, $model) {
                    if (! is_null($value)) {
                        $value = $this->replaceWithTagInstance($value);
                        $this->type ? $model->syncTagsWithType($value, $this->type) : $model->syncTags($value);
                    }
                };
            })
            ->tapIndexColumn(fn (MorphToManyColumn $column) => $column
                ->select($cols = ['type', 'display_order', 'swatch_color'])
                ->appends($cols)
                ->width('270px'))
            ->resolveForJsonResourceUsing(function (Model $model, string $attribute) {
                if ($model->relationLoaded('tags')) {
                    return [
                        $attribute => TagResource::collection($this->resolve($model)),
                    ];
                }
            });
    }

    /**
     * Provide the column used for index
     */
    public function indexColumn(): MorphToManyColumn
    {
        return new MorphToManyColumn(
            'tags',
            'name',
            $this->label,
        );
    }

    /**
     * Get the mailable template placeholder
     *
     * @param  \Modules\Core\App\Models\Model|null  $model
     */
    public function mailableTemplatePlaceholder($model)
    {
        return null;
    }

    /**
     * Add the type the tags are intended for.
     */
    public function forType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the options intended for zapier.
     */
    public function tagsForZapier(): array
    {
        return $this->getTags()
            ->map(fn (Tag $tag) => ([
                'value' => $tag->name,
                'label' => $tag->name,
            ]))
            ->all();
    }

    /**
     * Get all of the available tags for the field.
     */
    protected function getTags(): Collection
    {
        return $this->tags ??= Tag::query()->when(
            $this->type,
            fn (Builder $query) => $query->withType($this->type)
        )->get();
    }

    /**
     * Replace the given tag names with it's tag model instance.
     */
    protected function replaceWithTagInstance(array $tags): ArrayAccess
    {
        return collect($tags)->filter()->map(function (string $name) {
            if ($tag = $this->getTags()->first(
                fn (Tag $tag) => Str::lower($tag->name) === Str::lower($name))
            ) {
                return $tag;
            }

            // will be created
            return $name;
        });
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'type' => $this->type,
        ], request()->isZapier() ? [
            'options' => request()->isZapier() ? $this->tagsForZapier() : null,
            'labelKey' => 'label',
            'valueKey' => 'value',
        ] : []);
    }
}
