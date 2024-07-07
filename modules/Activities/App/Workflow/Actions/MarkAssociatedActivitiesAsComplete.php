<?php
 

namespace Modules\Activities\App\Workflow\Actions;

use Modules\Core\App\Workflow\Action;

class MarkAssociatedActivitiesAsComplete extends Action
{
    /**
     * Action name
     */
    public static function name(): string
    {
        return __('deals::deal.workflows.actions.mark_associated_activities_as_complete');
    }

    /**
     * Run the trigger.
     */
    public function run()
    {
        $this->model->incompleteActivities->each->markAsComplete();
    }

    /**
     * Action available fields
     */
    public function fields(): array
    {
        return [];
    }
}
