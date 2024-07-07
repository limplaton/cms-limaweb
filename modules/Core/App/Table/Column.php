<?php
 

namespace Modules\Core\App\Table;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use JsonSerializable;
use Modules\Core\App\Fields\Field;
use Modules\Core\App\Support\Authorizeable;
use Modules\Core\App\Support\HasHelpText;
use Modules\Core\App\Support\HasMeta;
use Modules\Core\App\Support\Makeable;

class Column implements Arrayable, JsonSerializable
{
    use Authorizeable,
        HasHelpText,
        HasMeta,
        Makeable;

    /**
     * Attributes to append with the response.
     */
    public array $appends = [];

    /**
     * Additional fields to select.
     */
    public array $select = [];

    /**
     * Eager load relationship with this field.
     */
    public array $with = [];

    /**
     * Count relationship with this field.
     */
    public array $withCount = [];

    /**
     * Custom query for this field.
     */
    public Expression|Closure|string|null $queryAs = null;

    /**
     * Indicates whether the column is sortable.
     */
    public bool $sortable = true;

    /**
     * Indicates whether the column is hidden.
     */
    public ?bool $hidden = null;

    /**
     * Indicates special columns and some actions are disabled.
     */
    public bool $primary = false;

    /**
     * Table th/td in px or "auto".
     */
    public string $width = 'auto';

    /**
     * Table th/td min width in px.
     */
    public ?string $minWidth = '100px';

    /**
     * The column default order.
     */
    public ?int $order = null;

    /**
     * Indicates whether to include the column in the query when it's hidden.
     */
    public bool $queryWhenHidden = false;

    /**
     * Data heading component.
     */
    public string $component = '';

    /**
     * @var callable|null
     */
    public $orderByUsing;

    /**
     * Indicates whether the column can be customized.
     */
    public bool $customizeable = true;

    /**
     * Indicates whether the column may contain new lines.
     */
    public bool $newlineable = false;

    /**
     * Indicates whether the column text should be wrapped.
     */
    public bool $wrap = false;

    /**
     * The column alignment, any of "center", "left", "right".
     */
    public string $align = 'left';

    /**
     * The field the column is related to.
     */
    public ?Field $field = null;

    /**
     * Custom row data filler callback.
     */
    public ?Closure $fillRowDataUsing = null;

    /**
     * Indicates whether the column is intended for the trashed table.
     */
    public static bool $trashed = false;

    /**
     * Initialize new Column instance.
     */
    public function __construct(public string $attribute, public ?string $label = null)
    {
    }

    /**
     * Check whether the column is intended to be used on the trashed table.
     */
    public function isForTrashedTable(): bool
    {
        return static::$trashed === true;
    }

    /**
     * Add a route for redirect on column click.
     */
    public function route(string|array $route): static
    {
        $this->withMeta([__FUNCTION__ => $route]);

        return $this;
    }

    /**
     * Add a link for redirect on column click.
     */
    public function link(string $href): static
    {
        $this->withMeta([__FUNCTION__ => $href]);

        return $this;
    }

    /**
     * Fill the row data
     */
    public function fillRowData(&$row, $model)
    {
        if ($this->fillRowDataUsing instanceof Closure) {
            call_user_func_array($this->fillRowDataUsing, [&$row, $model]);
        } else {
            if ($this instanceof RelationshipColumn) {
                $row[$this->attribute] = $model->getRelation($this->relationName);
            } else {
                $row[$this->attribute] = $model->getAttribute($this->attribute);
            }
        }
    }

    /**
     * Provide a closure to tap the model for the response.
     */
    public function fillRowDataUsing(Closure $callback): static
    {
        $this->fillRowDataUsing = $callback;

        return $this;
    }

    /**
     * Check whether the column should be included in the query.
     */
    public function shouldQuery(): bool
    {
        return ! $this->isHidden() || $this->queryWhenHidden;
    }

    /**
     * Custom query for this column.
     */
    public function queryAs(Expression|Closure|string $queryAs): static
    {
        $this->queryAs = $queryAs;

        return $this;
    }

    public function with(array|string $relationships)
    {
        $this->with = array_merge($this->withCount, (array) $relationships);

        return $this;
    }

    public function withCount(array|string $relationships)
    {
        $this->withCount = array_merge($this->withCount, (array) $relationships);

        return $this;
    }

    /**
     * Set column name/label.
     */
    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Set whether the column is sortable.
     */
    public function sortable(bool $bool = true): static
    {
        $this->sortable = $bool;

        return $this;
    }

    /**
     * Check whether the column is sortable.
     */
    public function isSortable(): bool
    {
        return $this->sortable === true;
    }

    /**
     * Set column visibility.
     */
    public function hidden(bool $bool = true): static
    {
        $this->hidden = $bool;

        return $this;
    }

    /**
     * Check whether the column is hidden.
     */
    public function isHidden(): bool
    {
        return $this->hidden === true;
    }

    /**
     * Mark the column as primary.
     * NOTE: It's recommended to have only 1 primary table column.
     */
    public function primary(bool $bool = true): static
    {
        $this->primary = $bool;

        return $this;
    }

    /**
     * Check whether the column is primary.
     */
    public function isPrimary(): bool
    {
        return $this->primary === true;
    }

    /**
     * Set column td custom class.
     */
    public function tdClass(string $class): static
    {
        $this->withMeta([__FUNCTION__ => $class]);

        return $this;
    }

    /**
     * Set column th custom class.
     */
    public function thClass(string $class): static
    {
        $this->withMeta([__FUNCTION__ => $class]);

        return $this;
    }

    /**
     * Set whether to wrap text for the column.
     */
    public function wrap(bool $wrap = true): static
    {
        $this->wrap = $wrap;

        return $this;
    }

    /**
     * Set column th/td min width in px.
     */
    public function width($width): static
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Set column th/td min width in px.
     */
    public function minWidth(?string $minWidth): static
    {
        $this->minWidth = $minWidth;

        return $this;
    }

    /**
     * Center the column heading and data.
     */
    public function centered(): static
    {
        $this->align('center');

        return $this;
    }

    /**
     * Add the alignment of the column data.
     */
    public function align(string $direction): static
    {
        $this->align = $direction;

        return $this;
    }

    /**
     * Set the column default order.
     */
    public function order(int $order): static
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Whether to select/query the column when the column hidden.
     */
    public function queryWhenHidden(bool $bool = true): static
    {
        $this->queryWhenHidden = $bool;

        return $this;
    }

    /**
     * Add additional fields to be selected when querying the column.
     */
    public function select(array|string $fields): static
    {
        $this->select = array_merge(
            $this->select,
            (array) $fields
        );

        return $this;
    }

    /**
     * Set attributes to appends in the model.
     */
    public function appends(array|string $attributes): static
    {
        $this->appends = array_merge(
            $this->appends,
            (array) $attributes
        );

        return $this;
    }

    /**
     * Check whether the column is a relation.
     */
    public function isRelation(): bool
    {
        return $this instanceof RelationshipColumn;
    }

    /**
     * Get the column data component.
     */
    public function component(): string
    {
        return $this->component;
    }

    /**
     * Set the column data component for the front-end.
     */
    public function useComponent(string $component): static
    {
        $this->component = $component;

        return $this;
    }

    /**
     * Apply the order by query for the column.
     */
    public function orderBy(Builder $query, string $direction): Builder
    {
        if (! $this->sortable) {
            return $query;
        }

        if (is_callable($this->orderByUsing)) {
            return call_user_func_array($this->orderByUsing, [$query, $direction, $this]);
        }

        return $query->orderBy($query->qualifyColumn($this->attribute), $direction);
    }

    /**
     * Order by using custom callback.
     */
    public function orderByUsing(callable $callback): static
    {
        $this->orderByUsing = $callback;

        return $this;
    }

    /**
     * Set whether the column can be customized.
     */
    public function customizeable(bool $value = true)
    {
        $this->customizeable = $value;

        return $this;
    }

    /**
     * Set the field the column is related to.
     */
    public function setField(Field $field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * toArray
     *
     * @return array
     */
    public function toArray()
    {
        return array_merge([
            'attribute' => $this->attribute,
            'label' => $this->label,
            'sortable' => $this->isSortable(),
            'hidden' => $this->isHidden(),
            'primary' => $this->isPrimary(),
            'component' => $this->component(),
            'align' => $this->align,
            'width' => $this->width,
            'minWidth' => $this->minWidth,
            'order' => $this->order,
            'helpText' => $this->helpText,
            'customizeable' => $this->customizeable,
            'newlineable' => $this->newlineable,
            'wrap' => $this->wrap,
            'field' => $this->field,
        ], $this->meta());
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
