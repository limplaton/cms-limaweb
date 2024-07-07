<?php
 

namespace Modules\Documents\App\Filters;

use Modules\Core\App\Filters\MultiSelect;
use Modules\Core\App\QueryBuilder\Parser;
use Modules\Documents\App\Enums\DocumentStatus;

class DocumentStatusFilter extends MultiSelect
{
    /**
     * Create new DocumentStatusFilter instance
     */
    public function __construct()
    {
        parent::__construct('status', __('documents::document.status.status'));

        $this->options(collect(DocumentStatus::cases())
            ->mapWithKeys(function (DocumentStatus $status) {
                return [$status->value => $status->displayName()];
            })->all())->query(function ($builder, $value, $condition, $sqlOperator, $rule, Parser $parser) {
                return $parser->makeArrayQueryIn(
                    $builder,
                    $rule,
                    $sqlOperator['operator'],
                    collect($value)->map(
                        fn ($status) => DocumentStatus::tryFrom($status)
                    )->filter()->all(),
                    $condition
                );
            });
    }
}
