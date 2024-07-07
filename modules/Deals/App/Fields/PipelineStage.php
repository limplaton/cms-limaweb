<?php
 

namespace Modules\Deals\App\Fields;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\App\Fields\BelongsTo;
use Modules\Core\App\Table\BelongsToColumn;
use Modules\Deals\App\Http\Resources\StageResource;
use Modules\Deals\App\Models\Pipeline;
use Modules\Deals\App\Models\Stage;

class PipelineStage extends BelongsTo
{
    /**
     * Field component.
     *
     * @var string
     */
    public static $component = 'pipeline-stage-field';

    /**
     * Creat new PipelineStage instance field
     *
     * @param  string|null  $label
     */
    public function __construct($label = null)
    {
        parent::__construct('stage', Stage::class, $label ?: __('deals::fields.deals.stage.name'));

        $this->setJsonResource(StageResource::class)
            ->creationRules('required')
            ->updateRules(['required_with:pipeline_id', 'filled'])
            ->rules(function (string $attribute, mixed $value, Closure $fail) {
                // If no value, fails on the required rule
                if ($value && is_null(Pipeline::visible()
                    ->whereHas('stages', fn ($query) => $query->where('id', $value))
                    ->first()) &&
                    // when the user is allowed to edit the deal but not allowed to view the pipeline
                    // allow only the pipeline stage to be changed, but we don't allow the pipeline itself to be changed.
                    ! $this->resolveRequest()->isUpdateRequest()
                ) {
                    $fail('The :attribute value is forbidden.');
                }
            })
            ->withDefaultValue(function () {
                // First visible/ordered pipeline is selected for the Pipeline field as well
                // in this case, we will use the same first pipeline to retrieve the first stage
                return Pipeline::with('stages')
                    ->visible()
                    ->userOrdered()
                    ->first()
                    ->stages
                    ->first();
            })
            ->required()
            ->acceptLabelAsValue(false)
            ->tapIndexColumn(function (BelongsToColumn $column) {
                $column
                    ->select(['pipeline_id', 'display_order'])
                    ->appends('pipeline_id')
                    ->orderByUsing(function (Builder $query, string $direction, string $alias) {
                        return $query->orderBy(
                            $alias.'.display_order', $direction
                        );
                    });
            })
            ->withoutClearAction();
    }

    /**
     * Provides the PipelineStage instance options
     *
     * We using dependable field, we need to provide all the options
     */
    public function resolveOptions(): array
    {
        return Stage::get()->all();
    }
}
