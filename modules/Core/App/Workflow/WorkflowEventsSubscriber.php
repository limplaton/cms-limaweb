<?php
 

namespace Modules\Core\App\Workflow;

use Illuminate\Events\Dispatcher;
use Modules\Core\App\Models\Workflow;

class WorkflowEventsSubscriber
{
    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events)
    {
        foreach (Workflows::$eventOnlyListeners as $data) {
            $events->listen($data['event'], function ($event) use ($data) {
                $workflows = Workflow::byTrigger($data['trigger'])->get();

                foreach ($workflows as $workflow) {
                    Workflows::process($workflow, ['event' => $event]);
                }
            });
        }
    }
}
