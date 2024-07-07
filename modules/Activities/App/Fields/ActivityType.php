<?php
 

namespace Modules\Activities\App\Fields;

use Modules\Activities\App\Http\Resources\ActivityTypeResource;
use Modules\Activities\App\Models\ActivityType as ActivityTypeModel;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Fields\BelongsTo;
use Modules\Core\App\Table\Column;

class ActivityType extends BelongsTo
{
    /**
     * Field component.
     */
    public static $component = 'activity-type-field';

    /**
     * Create new instance of ActivityType field.
     */
    public function __construct()
    {
        parent::__construct('type', ActivityTypeModel::class, __('activities::activity.type.type'));

        $this
            ->withDefaultValue(function () {
                if (is_null($type = ActivityTypeModel::getDefaultType())) {
                    return null;
                }

                return ActivityTypeModel::select('id')->find($type)?->getKey();
            })
            ->inlineEditWith(
                BelongsTo::make('type', ActivityTypeModel::class, __('activities::activity.type.type'))
                    ->valueKey('id')
                    ->labelKey('name')
                    ->rules('required')
                    ->withoutClearAction()
                    ->options(
                        Innoclapps::resourceByModel(ActivityTypeModel::class)
                    )
            )
            ->setJsonResource(ActivityTypeResource::class)
            ->tapIndexColumn(function (Column $column) {
                $column->select($cols = ['icon', 'swatch_color'])->appends($cols)->width('200px');
            })
            ->options(Innoclapps::resourceByModel(ActivityTypeModel::class))
            ->acceptLabelAsValue(false);
    }
}
