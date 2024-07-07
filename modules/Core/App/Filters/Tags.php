<?php
 

namespace Modules\Core\App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\App\Models\Tag;
use Modules\Core\App\QueryBuilder\Parser;

class Tags extends Optionable
{
    /**
     * The type the tags are intended for.
     */
    protected ?string $type = null;

    /**
     * @param  string  $field
     * @param  string|null  $label
     * @param  null|array  $operators
     */
    public function __construct($field, $label = null, $operators = null)
    {
        parent::__construct($field, $label, $operators);

        $this->options(function () {
            return Tag::query()
                ->when($this->type, function (Builder $query) {
                    return $query->withType($this->type);
                })
                ->get()
                ->map(fn (Tag $tag) => [
                    $this->valueKey => $tag->id,
                    $this->labelKey => $tag->name,
                    'swatch_color' => $tag->swatch_color,
                ]);
        })->query($this->getQuery(...));
    }

    /**
     * Defines a filter type
     */
    public function type(): string
    {
        return 'multi-select';
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
     * Get the query for the filter.
     */
    protected function getQuery($builder, $value, $condition, $sqlOperator, $rule, Parser $parser)
    {
        return $builder->whereHas(
            'tags',
            function ($query) use ($value, $rule, $sqlOperator, $parser, $condition) {
                $query->when(
                    $this->type,
                    fn (Builder $query) => $query->withType($this->type)
                );

                $rule->query->rule = 'id';

                return $parser->convertToQuery($query, $rule, $value, $sqlOperator['operator'], $condition);
            }
        );
    }
}
