<?php
 

namespace Modules\Core\App\Criteria;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\App\Contracts\Criteria\QueryCriteria;
use Modules\Core\App\Support\Carbon;
use Modules\Core\App\Support\ProvidesBetweenArgumentsViaString;

class ExportRequestCriteria implements QueryCriteria
{
    use ProvidesBetweenArgumentsViaString;

    /**
     * Create new ExportRequestCriteria instance.
     */
    public function __construct(protected string|array|null $period, protected ?string $dateRangeField = null)
    {
    }

    /**
     * Apply the criteria for the given query.
     */
    public function apply(Builder $query): void
    {
        $dateRangeField = $this->determineDateRangeField($query);

        if ($this->period) {
            $query->whereBetween($dateRangeField, $this->periodToValue());
        }

        $query->orderByDesc($dateRangeField);
    }

    /**
     * Convert the period to database "WHERE" value.
     */
    protected function periodToValue(): array
    {
        if (is_array($this->period)) {
            return array_map(fn ($date) => Carbon::fromCurrentToAppTimezone($date), $this->period);
        }

        return $this->getBetweenArguments($this->period);
    }

    /**
     * Determine the date range field attribute.
     */
    protected function determineDateRangeField($query): string
    {
        $dateRangeField = $this->dateRangeField;

        if (empty($dateRangeField)) {
            if (! $query->getModel()->usesTimestamps()) {
                throw new Exception('Exportable resource model must use timestamps.');
            }

            $dateRangeField = $query->getModel()->getCreatedAtColumn();
        }

        return $dateRangeField;
    }
}
