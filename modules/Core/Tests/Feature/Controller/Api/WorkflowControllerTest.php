<?php
 

namespace Modules\Core\Tests\Feature\Controller\Api;

use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\Fluent\AssertableJson;
use Modules\Core\App\Models\Workflow;
use Modules\Core\App\Workflow\Workflows;
use Modules\Deals\App\Models\Pipeline;
use Modules\Users\App\Models\User;
use Tests\Fixtures\Workflows\ContactCreatedTrigger;
use Tests\Fixtures\Workflows\ContactUserChangedTrigger;
use Tests\Fixtures\Workflows\CreateDealAction;
use Tests\TestCase;

class WorkflowControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Workflows::triggers([ContactCreatedTrigger::class, ContactUserChangedTrigger::class]);
    }

    public function test_unauthenticated_user_cannot_access_workflows_endpoints()
    {
        $workflow = $this->createWorkflow();

        $this->getJson('/api/workflows')->assertUnauthorized();
        $this->getJson("/api/workflows/$workflow->id")->assertUnauthorized();
        $this->postJson('/api/workflows')->assertUnauthorized();
        $this->putJson("/api/workflows/$workflow->id")->assertUnauthorized();
        $this->deleteJson("/api/workflows/$workflow->id")->assertUnauthorized();
    }

    public function test_unauthorized_user_cannot_access_workflows_endpoints()
    {
        $this->asRegularUser()->signIn();

        $workflow = $this->createWorkflow();

        $this->getJson('/api/workflows')->assertForbidden();
        $this->getJson("/api/workflows/$workflow->id")->assertForbidden();
        $this->postJson('/api/workflows')->assertForbidden();
        $this->putJson("/api/workflows/$workflow->id")->assertForbidden();
        $this->deleteJson("/api/workflows/$workflow->id")->assertForbidden();
    }

    public function test_worklow_can_be_created()
    {
        $user = $this->signIn();

        $pipeline = Pipeline::factory()->withStages()->create();
        $stageId = $pipeline->stages->get(2)->id;

        $id = $this->postJson('/api/workflows', $this->worklowArray([
            'name' => 'Deal Name',
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stageId,
            'amount' => 1000,
            'user_id' => $user->id,
        ]))->assertCreated()
            ->assertJson([
                'title' => 'Title',
                'description' => 'Description',
                'is_active' => true,
                'action_type' => CreateDealAction::class,
                'trigger_type' => ContactCreatedTrigger::class,
            ])
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.name', 'Deal Name')
            ->assertJsonPath('data.pipeline_id', $pipeline->id)
            ->assertJsonPath('data.stage_id', $stageId)
            ->assertJsonPath('data.amount', 1000)
            ->getData()->id;

        $this->assertDatabaseHas('workflows', ['created_by' => $user->id, 'id' => $id]);
    }

    public function test_worklow_can_be_updated()
    {
        $user = $this->signIn();

        $pipeline = Pipeline::factory()->withStages()->create();
        $stageId = $pipeline->stages->get(3)->id;

        $id = $this->createWorkflow([
            'name' => 'Deal Name',
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stageId,
            'amount' => 1000,
            'user_id' => $user->id,
        ])->id;

        $this->putJson('/api/workflows/'.$id, [
            'title' => 'Changed Title',
            'description' => 'Changed Description',
            'is_active' => false,
            'action_type' => CreateDealAction::class,
            'trigger_type' => ContactCreatedTrigger::class,
            'name' => 'Changed Name',
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stageId,
            'amount' => 1500,
            'user_id' => $user->id,
        ])->assertOk()->assertJson([
            'title' => 'Changed Title',
            'description' => 'Changed Description',
            'is_active' => false,
            'action_type' => CreateDealAction::class,
            'trigger_type' => ContactCreatedTrigger::class,
        ])
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.name', 'Changed Name')
            ->assertJsonPath('data.pipeline_id', $pipeline->id)
            ->assertJsonPath('data.stage_id', $stageId)
            ->assertJsonPath('data.amount', 1500);
    }

    public function test_worklow_can_be_deleted()
    {
        $this->signIn();

        $workflow = $this->createWorkflow();

        $this->deleteJson('/api/workflows/'.$workflow->id)->assertNoContent();
        $this->assertModelMissing($workflow);
    }

    public function test_worklow_can_be_retrieved()
    {
        $this->signIn();
        $workflow = $this->createWorkflow(['is_active' => false]);

        $this->getJson('/api/workflows/'.$workflow->id)->assertJson([
            'id' => $workflow->id,
            'title' => $workflow->title,
            'description' => $workflow->description,
            'is_active' => false,
            'trigger_type' => $workflow->trigger_type,
            'action_type' => $workflow->action_type,
            'created_at' => $workflow->created_at->toJSON(),
            'updated_at' => $workflow->updated_at->toJSON(),
        ]);
    }

    public function test_worklows_can_be_retrieved()
    {
        $this->signIn();

        $workflow = $this->createWorkflow(['is_active' => false]);

        $this->getJson('/api/workflows')->assertJson(function (AssertableJson $json) use ($workflow) {
            $json->has(1)->first(function ($json) use ($workflow) {
                $json->where('id', $workflow->id)
                    ->where('title', 'Title')
                    ->where('description', 'Description')
                    ->where('is_active', false)
                    ->where('action_type', CreateDealAction::class)
                    ->where('trigger_type', ContactCreatedTrigger::class)
                    ->etc();
            });
        });
    }

    public function test_workflow_requires_title()
    {
        $this->signIn();

        $this->postJson('/api/workflows', $this->worklowArray(['title' => null]))
            ->assertJsonValidationErrors('title');
    }

    public function test_workflow_requires_trigger_type()
    {
        $this->signIn();

        $this->postJson('/api/workflows', $this->worklowArray(['trigger_type' => null]))
            ->assertJsonValidationErrors('trigger_type');
    }

    public function test_workflow_requires_valid_trigger_type()
    {
        $this->signIn();

        $this->postJson('/api/workflows', $this->worklowArray(['trigger_type' => 'dummy']))
            ->assertJsonValidationErrors(['trigger_type' => 'The selected trigger type is invalid.']);
    }

    public function test_workflow_trigger_of_type_field_change_field_value_is_merged_in_data_attribute()
    {
        $this->signIn();

        $this->postJson('/api/workflows', $this->worklowArray([
            'trigger_type' => ContactUserChangedTrigger::class,
            ContactUserChangedTrigger::changeField()->attribute => 15,
        ]))
            ->assertJsonPath('data.'.ContactUserChangedTrigger::changeField()->attribute, 15);
    }

    public function test_workflow_trigger_of_type_field_change_field_is_required()
    {
        $this->signIn();

        $payload = $this->worklowArray([
            'trigger_type' => ContactUserChangedTrigger::class,
            ContactUserChangedTrigger::changeField()->attribute => null,
        ]);

        $this->postJson('/api/workflows', $payload)
            ->assertJsonValidationErrors(ContactUserChangedTrigger::changeField()->attribute);
    }

    public function test_workflow_requires_valid_action_type()
    {
        $this->signIn();

        $this->postJson('/api/workflows', $this->worklowArray(['action_type' => 'dummy']))
            ->assertJsonValidationErrors(['action_type' => 'The action field must exist in the trigger available actions.']);
    }

    public function test_workflow_accepts_action_only_from_the_trigger_defined_actions()
    {
        $this->signIn();

        $this->postJson('/api/workflows', $this->worklowArray(['action_type' => 'dummy']))
            ->assertJsonValidationErrors(['action_type' => __('validation.in_array', [
                'attribute' => 'action',
                'other' => 'the trigger available actions',
            ])]);
    }

    public function test_workflow_requires_action_type()
    {
        $this->signIn();

        $this->postJson('/api/workflows', $this->worklowArray(['action_type' => null]))
            ->assertJsonValidationErrors('action_type');
    }

    protected function createWorkflow($attributes = [])
    {
        $workfow = new Workflow($this->worklowArray($attributes));
        $workfow->created_by = Auth::check() ? Auth::id() : User::factory()->create()->id;
        $workfow->save();

        return $workfow;
    }

    protected function worklowArray($attributes = [])
    {
        return array_merge([
            'title' => 'Title',
            'description' => 'Description',
            'is_active' => true,
            'action_type' => CreateDealAction::class,
            'trigger_type' => ContactCreatedTrigger::class,
        ], $attributes);
    }
}
