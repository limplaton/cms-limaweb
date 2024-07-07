<?php
 

namespace Modules\Deals\App\Fields;

use Modules\Core\App\Fields\BelongsTo;
use Modules\Core\App\Rules\VisibleModelRule;
use Modules\Deals\App\Http\Resources\PipelineResource;
use Modules\Deals\App\Models\Pipeline as PipelineModel;

class Pipeline extends BelongsTo
{
    /**
     * Creat new Pipeline instance field
     *
     * @param  string  $label  Custom label
     */
    public function __construct($label = null)
    {
        parent::__construct('pipeline', PipelineModel::class, $label ?: __('deals::fields.deals.pipeline.name'));

        $this->setJsonResource(PipelineResource::class)
            ->rules(
                (new VisibleModelRule(new PipelineModel))
                    ->ignore(
                        fn () => with($this->resolveRequest(), function ($request) {
                            return $request->isUpdateRequest() ? $request->record()->pipeline : null;
                        })
                    )
            )
            ->emitChangeEvent()
            ->withDefaultValue(function () {
                return PipelineModel::withCommon()
                    ->with('stages')
                    ->visible()
                    ->userOrdered()
                    ->first();
            })
            ->acceptLabelAsValue()
            ->withoutClearAction();
    }

    /**
     * Provides the BelongsTo instance options
     */
    public function resolveOptions(): array
    {
        return PipelineModel::select(['id', 'name'])
            ->with('stages')
            ->visible()
            ->userOrdered()
            ->get()
            ->all();
    }
}
