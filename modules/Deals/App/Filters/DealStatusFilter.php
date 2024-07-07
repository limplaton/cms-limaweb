<?php
 

namespace Modules\Deals\App\Filters;

use Modules\Core\App\Filters\Select;
use Modules\Deals\App\Enums\DealStatus as StatusEnum;

class DealStatusFilter extends Select
{
    /**
     * Initialize Source class
     */
    public function __construct()
    {
        parent::__construct('status', __('deals::deal.status.status'));

        $this->options(collect(StatusEnum::cases())->mapWithKeys(function (StatusEnum $status) {
            return [$status->name => $status->label()];
        })->all());

        $this->query(function ($builder, $value, $condition, $sqlOperator) {
            return $builder->where($this->field, $sqlOperator['operator'], StatusEnum::find($value), $condition);
        });
    }
}
