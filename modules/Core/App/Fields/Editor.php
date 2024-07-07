<?php
 

namespace Modules\Core\App\Fields;

use Modules\Core\App\Common\Placeholders\GenericPlaceholder;
use Modules\Core\App\Contracts\Fields\Deleteable;
use Modules\Core\App\Fields\Deleteable as DeleteableTrait;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Models\Model;
use Modules\Users\App\Mention\PendingMention;

class Editor extends Field implements Deleteable
{
    use DeleteableTrait;

    /**
     * Field component.
     */
    public static $component = 'editor-field';

    /**
     * The inline edit popover width (medium|large).
     */
    public string $inlineEditPanelWidth = 'large';

    /**
     * Initialize new Editor instance.
     */
    public function __construct()
    {
        parent::__construct(...func_get_args());

        $this
            ->deleteUsing(function (Model $model) {
                // as an example
            })
            ->fillUsing(function (Model $model, string $attribute, ResourceRequest $request, ?string $value) {
                $mention = new PendingMention($value ?: '');

                if ($mention->hasMentions()) {
                    $value = $mention->getUpdatedText();
                }

                $model->{$attribute} = $value;

                return function () use ($mention, $model, $request) {
                    $intermediate = $request->viaResource() ?
                        $request->findResource($request->via_resource)->newQuery()->find($request->via_resource_id) :
                        $model;

                    $mention->setUrl($intermediate->path())->withUrlQueryParameter([
                        'section' => $request->viaResource() ? $model->resource()->name() : null,
                        'resourceId' => $request->viaResource() ? $model->getKey() : null,
                    ])->notify();
                };
            })
            ->resolveUsing(fn ($model, $attribute) => clean($model->{$attribute}));
    }

    /**
     * Get the mailable template placeholder
     *
     * @param  \Modules\Core\App\Models\Model|null  $model
     * @return \Modules\Core\App\Common\Placeholders\GenericPlaceholder
     */
    public function mailableTemplatePlaceholder($model)
    {
        return GenericPlaceholder::make($this->attribute)
            ->description($this->label)
            ->withStartInterpolation('{{{')
            ->withEndInterpolation('}}}')
            ->value(fn () => $this->resolveForDisplay($model));
    }

    /**
     * Add mention support to the editor.
     */
    public function withMentions(): static
    {
        $this->withMeta([
            'attributes' => [
                'with-mention' => true,
            ],
        ]);

        return $this;
    }

    /**
     * Mark the editor as mininmal.
     */
    public function minimal(): static
    {
        $this->withMeta([
            'attributes' => [
                'minimal' => true,
            ],
        ]);

        return $this;
    }

    /**
     * Prepare the field when it's intended to be used on the bulk edit action.
     */
    public function prepareForBulkEdit(): void
    {
        unset($this->meta['attributes']['with-mention']);

        parent::prepareForBulkEdit();
    }
}
