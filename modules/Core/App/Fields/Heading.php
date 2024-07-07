<?php
 

namespace Modules\Core\App\Fields;

use Exception;
use Modules\Core\App\Table\Column;

class Heading extends Field
{
    /**
     * Field component.
     */
    public static $component = 'heading-field';

    /**
     * Field title icon
     */
    public ?string $titleIcon = null;

    /**
     * Initialize new Heading instance.
     */
    public function __construct(public string $title, public ?string $message = null)
    {
        $this->excludeFromImport()
            ->excludeFromExport()
            ->excludeFromSettings()
            ->excludeFromIndex();
    }

    /**
     * Set field title
     */
    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set field message
     */
    public function message(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Set field title icon
     */
    public function titleIcon(?string $icon): static
    {
        $this->titleIcon = $icon;

        return $this;
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
     * Provide the column used for index
     */
    public function indexColumn(): ?Column
    {
        return null;
    }

    /**
     * Resolve the actual field value
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return mixed
     */
    public function resolve($model)
    {
        return null;
    }

    /**
     * Resolve the displayable field value
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function resolveForDisplay($model)
    {
        return null;
    }

    /**
     * Resolve the field value for export
     *
     * @param  \Modules\Core\App\Models\Model  $model
     */
    public function resolveForExport($model)
    {
        return null;
    }

    /**
     * Resolve the field value for JSON Resource
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function resolveForJsonResource($model)
    {
        return null;
    }

    /**
     * Add custom value resolver
     */
    public function resolveUsing(callable $resolveCallback): never
    {
        throw new Exception(__CLASS__.' cannot have custom resolve callback.');
    }

    /**
     * Add custom display resolver
     */
    public function displayUsing(callable $displayCallback): never
    {
        throw new Exception(__CLASS__.' cannot have custom display callback.');
    }

    /**
     * Add custom fill callback for the field.
     */
    public function fillUsing(callable $callback): static
    {
        throw new Exception(__CLASS__.' cannot have custom fill callback.');
    }

    /**
     * Add custom JSON resource callback.
     */
    public function resolveForJsonResourceUsing(callable $callback): static
    {
        throw new Exception(__CLASS__.' cannot have custom JSON resource callback.');
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'title' => $this->title,
            'message' => $this->message,
            'titleIcon' => $this->titleIcon,
        ]);
    }
}
