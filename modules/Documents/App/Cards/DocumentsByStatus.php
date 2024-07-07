<?php
 

namespace Modules\Documents\App\Cards;

use Illuminate\Http\Request;
use Modules\Core\App\Charts\Presentation;
use Modules\Documents\App\Criteria\ViewAuthorizedDocumentsCriteria;
use Modules\Documents\App\Enums\DocumentStatus;
use Modules\Documents\App\Models\Document;

class DocumentsByStatus extends Presentation
{
    /**
     * The default renge/period selected
     *
     * @var int
     */
    public string|int|null $defaultRange = 30;

    /**
     * Calculates companies by source
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        $query = Document::criteria(ViewAuthorizedDocumentsCriteria::class);

        return $this->byDays('created_at')
            ->count($request, $query, 'status')
            ->label(function ($value) {
                return DocumentStatus::tryFrom($value)->displayName();
            })->colors(collect(DocumentStatus::cases())->mapWithKeys(function (DocumentStatus $status) {
                return [$status->displayName() => $status->color()];
            })->all());
    }

    /**
     * Get the ranges available for the chart.
     */
    public function ranges(): array
    {
        return [
            7 => __('core::dates.periods.7_days'),
            15 => __('core::dates.periods.15_days'),
            30 => __('core::dates.periods.30_days'),
            60 => __('core::dates.periods.60_days'),
            90 => __('core::dates.periods.90_days'),
            365 => __('core::dates.periods.365_days'),
        ];
    }

    /**
     * The card name
     */
    public function name(): string
    {
        return __('documents::document.cards.by_status');
    }
}
