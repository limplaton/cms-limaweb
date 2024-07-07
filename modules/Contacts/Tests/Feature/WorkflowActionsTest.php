<?php
 

namespace Modules\Contacts\Tests\Feature;

use Illuminate\Support\Arr;
use Modules\Activities\App\Models\Activity;
use Modules\Activities\App\Workflow\Actions\CreateActivityAction;
use Modules\Contacts\App\Models\Company;
use Modules\Contacts\App\Models\Contact;
use Modules\Contacts\App\Workflow\Triggers\CompanyCreated;
use Modules\Contacts\App\Workflow\Triggers\ContactCreated;
use Modules\Core\App\Models\Workflow;
use Modules\Core\App\Workflow\Workflows;
use Tests\TestCase;

class WorkflowActionsTest extends TestCase
{
    public function test_company_created_workflow_triggers_create_activity_action()
    {
        $user = $this->signIn();

        $this->createActivityWorkflow(CompanyCreated::class, $user->id);

        $this->postJson('/api/companies', [
            'name' => 'Acme',
        ]);

        $model = Company::first();

        $this->assertCount(1, $model->activities);
        $this->assertArrayHasKey(CreateActivityAction::class, Workflows::$processed);
    }

    public function test_contact_created_workflow_triggers_create_activity_action()
    {
        $user = $this->signIn();

        $this->createActivityWorkflow(ContactCreated::class, $user->id);

        $this->postJson('/api/contacts', [
            'first_name' => 'John',
        ]);

        $model = Contact::first();

        $this->assertCount(1, $model->activities);
        $this->assertArrayHasKey(CreateActivityAction::class, Workflows::$processed);
    }

    protected function createActivityWorkflow($forTrigger, $userId, $attributes = [])
    {
        $activityAttributes = Activity::factory()->make()->toArray();
        $action = new CreateActivityAction;
        $fields = collect($action->fields())->pluck('attribute')->all();

        $attributes = array_merge(Arr::only($activityAttributes, $fields), [
            'activity_title' => $activityAttributes['title'],
            'user_id' => $userId,
            'due_date' => 'in_2_days',
        ], $attributes);

        $workflow = $this->createWorkflow($forTrigger, get_class($action), ['data' => $attributes], $userId);
        $attributes['title'] = $attributes['activity_title'];
        unset($attributes['activity_title']);

        return [
            $workflow,
            $attributes,
        ];
    }

    protected function createWorkflow($trigger, $action, $attributes = [], $userId = 1)
    {
        $workflow = new Workflow($this->makeWorkflow(array_merge(
            ['trigger_type' => $trigger, 'action_type' => $action, 'created_by' => $userId],
            $attributes
        )));

        $workflow->save();

        return $workflow;
    }

    protected function makeWorkflow($attributes = [])
    {
        return array_merge([
            'title' => 'Title',
            'description' => 'Description',
            'is_active' => true,
        ], $attributes);
    }
}
