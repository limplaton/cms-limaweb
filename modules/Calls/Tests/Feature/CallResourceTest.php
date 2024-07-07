<?php
 

namespace Modules\Calls\Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Modules\Calls\App\Models\Call;
use Modules\Calls\App\Models\CallOutcome;
use Modules\Contacts\App\Models\Contact;
use Modules\Core\Tests\ResourceTestCase;
use Modules\Users\App\Notifications\UserMentioned;
use Modules\Users\Tests\Concerns\TestsMentions;

class CallResourceTest extends ResourceTestCase
{
    use TestsMentions;

    protected $resourceName = 'calls';

    public function test_user_can_create_resource_record()
    {
        $this->signIn();

        $contact = Contact::factory()->create();
        $outcome = CallOutcome::factory()->create();

        $response = $this->postJson($this->createEndpoint(), [
            'body' => 'Call Body',
            'call_outcome_id' => $outcome->id,
            'date' => '2021-12-10 12:00:00',
            'via_resource' => 'contacts',
            'via_resource_id' => $contact->id,
            'contacts' => [$contact->id],
        ]);

        $this->assertResourceJsonStructure($response);

        $response->assertCreated()->assertJson([
            'was_recently_created' => true,
            'body' => 'Call Body',
            'date' => Carbon::parse('2021-12-10 12:00:00')->toJSON(),
            'call_outcome_id' => $outcome->id,
        ]);

        $this->assertCount(1, Call::first()->contacts);
    }

    public function test_user_can_create_resource_record_with_associations_attribute()
    {
        $this->signIn();

        $contact = Contact::factory()->create();
        $outcome = CallOutcome::factory()->create();

        $this->postJson($this->createEndpoint(), [
            'body' => 'Call Body',
            'call_outcome_id' => $outcome->id,
            'date' => '2021-12-10 12:00:00',
            'via_resource' => 'contacts',
            'via_resource_id' => $contact->id,
            'associations' => [
                'contacts' => [$contact->id],
            ],
        ])->assertCreated();

        $this->assertCount(1, Call::first()->contacts);
    }

    public function test_user_can_update_resource_record()
    {
        $this->signIn();
        $call = $this->factory()->create();
        $contact = Contact::factory()->create();
        $outcome = CallOutcome::factory()->create();

        $response = $this->putJson($this->updateEndpoint($call), [
            'body' => 'Updated Body',
            'date' => '2021-12-10 12:00:00',
            'call_outcome_id' => $outcome->id,
            'via_resource' => 'contacts',
            'via_resource_id' => $contact->id,
        ]);

        $this->assertResourceJsonStructure($response);

        $response->assertOk()->assertJson([
            'body' => 'Updated Body',
            'date' => Carbon::parse('2021-12-10 12:00:00')->toJSON(),
            'call_outcome_id' => $outcome->id,
        ]);
    }

    public function test_user_can_update_only_own_created_call()
    {
        $user = $this->asRegularUser()->createUser();
        $contact = Contact::factory()->create();
        $this->signIn($user);
        $user2 = $this->createUser();
        $call = $this->factory()->for($user2)->create();

        $this->putJson($this->updateEndpoint($call), [
            'body' => 'Updated Body',
            'call_outcome_id' => $call->call_outcome_id,
            'date' => '2021-12-10 12:00:00',
            'via_resource' => 'contacts',
            'via_resource_id' => $contact->id,
        ])->assertForbidden();
    }

    public function test_call_requires_body()
    {
        $this->signIn();
        $call = $this->factory()->create();

        $this->postJson($this->createEndpoint(), [
            'body' => '',
        ])->assertJsonValidationErrorFor('body');

        $this->putJson($this->updateEndpoint($call), [
            'body' => '',
        ])->assertJsonValidationErrorFor('body');
    }

    public function test_call_requires_date()
    {
        $this->signIn();
        $call = $this->factory()->create();

        $this->postJson($this->createEndpoint(), [
            'date' => '',
        ])->assertJsonValidationErrorFor('date');

        $this->putJson($this->updateEndpoint($call), [
            'date' => '',
        ])->assertJsonValidationErrorFor('date');
    }

    public function test_call_requires_valid_date()
    {
        $this->signIn();
        $call = $this->factory()->create();

        $this->postJson($this->createEndpoint(), [
            'date' => 'invalid',
        ])->assertJsonValidationErrorFor('date');

        $this->putJson($this->updateEndpoint($call), [
            'date' => 'invalid',
        ])->assertJsonValidationErrorFor('date');
    }

    public function test_call_requires_call_outcome_id()
    {
        $this->signIn();
        $call = $this->factory()->create();

        $this->postJson($this->createEndpoint(), [
            'call_outcome_id' => '',
        ])->assertJsonValidationErrorFor('call_outcome_id');

        $this->putJson($this->updateEndpoint($call), [
            'call_outcome_id' => '',
        ])->assertJsonValidationErrorFor('call_outcome_id');
    }

    public function test_call_requires_via_resource()
    {
        $this->signIn();
        $call = $this->factory()->create();
        $contact = Contact::factory()->create();

        $this->postJson($this->createEndpoint(), [
            'body' => 'Call Body',
            'via_resource_id' => $contact->id,
            'via_resource' => '',
        ])->assertJsonValidationErrorFor('via_resource');
        $this->putJson($this->updateEndpoint($call), [
            'body' => 'Call Body',
            'via_resource_id' => $contact->id,
            'via_resource' => '',
        ])->assertJsonValidationErrorFor('via_resource');
    }

    public function test_call_requires_via_resource_id()
    {
        $this->signIn();
        $call = $this->factory()->create();

        $this->postJson($this->createEndpoint(), [
            'body' => 'Call Body',
            'via_resource' => 'contacts',
            'via_resource_id' => '',
        ])->assertJsonValidationErrorFor('via_resource_id');

        $this->putJson($this->updateEndpoint($call), [
            'body' => 'Call Body',
            'via_resource' => 'contacts',
            'via_resource_id' => '',
        ])->assertJsonValidationErrorFor('via_resource_id');
    }

    public function test_user_can_retrieve_resource_records()
    {
        $this->signIn();

        $this->factory()->count(5)->create();

        $this->getJson($this->indexEndpoint())->assertJsonCount(5, 'data');
    }

    public function test_user_can_retrieve_calls_that_are_associated_with_related_records_the_user_is_authorized_to_see()
    {
        $user = $this->asRegularUser()->withPermissionsTo('view own contacts')->createUser();
        $this->signIn($user);
        $user2 = $this->createUser();
        $this->factory()->create();
        $this->factory()->for($user2)->create();
        $this->factory()->for($user)->has(Contact::factory()->for($user))->create();

        $this->getJson($this->indexEndpoint())->assertJsonCount(1, 'data');
    }

    public function test_user_can_retrieve_call_associated_with_related_record_the_user_is_authorized_to_see()
    {
        $user = $this->asRegularUser()->withPermissionsTo('view own contacts')->createUser();
        $this->signIn($user);
        $otherUser = $this->createUser();
        $call = $this->factory()->for($otherUser)->has(Contact::factory()->for($user))->create();

        $endpoint = $this->showEndpoint($call).'?via_resource=contacts&via_resource_id='.$call->contacts->first()->getKey();

        $this->getJson($endpoint)->assertOk();
    }

    public function test_user_can_retrieve_resource_record()
    {
        $this->signIn();

        $record = $this->factory()->create();

        $this->getJson($this->showEndpoint($record))->assertOk();
    }

    public function test_user_can_retrieve_only_own_created_call()
    {
        $user = $this->asRegularUser()->createUser();
        $this->signIn($user);
        $user2 = $this->createUser();
        $call = $this->factory()->for($user2)->create();

        $this->getJson($this->showEndpoint($call))->assertForbidden();
    }

    public function test_user_can_delete_resource_record()
    {
        $this->signIn();

        $record = $this->factory()->create();

        $this->deleteJson($this->deleteEndpoint($record))->assertNoContent();
    }

    public function test_user_can_delete_only_own_created_call()
    {
        $user = $this->asRegularUser()->createUser();
        $this->signIn($user);
        $user2 = $this->createUser();
        $call = $this->factory()->for($user2)->create();

        $this->deleteJson($this->deleteEndpoint($call))->assertForbidden();
    }

    protected function assertResourceJsonStructure($response)
    {
        $response->assertJsonStructure([
            'actions', 'body', 'call_outcome_id', 'comments_count', 'created_at', 'date', 'id', 'outcome', 'timeline_component', 'timeline_key', 'timeline_relation', 'updated_at', 'user', 'user_id', 'was_recently_created', 'authorizations' => [
                'create', 'delete', 'update', 'view', 'viewAny',
            ],
        ]);
    }

    public function test_user_can_retrieve_calls_related_to_associations_authorized_to_view()
    {
        $user = $this->asRegularUser()->withPermissionsTo('view own contacts')->signIn();
        $call = $this->factory()->has(Contact::factory()->for($user))->create();
        $contact = $call->contacts[0];

        $this->getJson("/api/calls/$call->id?via_resource=contacts&via_resource_id=$contact->id")->assertOk();
    }

    public function test_it_eager_loads_relations_when_retrieving_via_associated_record()
    {
        $this->signIn();

        $call = $this->factory()->has(Contact::factory())->create();

        $call->comments()->create(['body' => 'Test']);

        $contact = $call->contacts[0];

        $this->getJson("/api/contacts/$contact->id/calls")->assertJsonStructure([
            'data' => [
                ['user', 'comments_count'],
            ],
        ])->assertJsonPath('data.0.comments_count', 1);
    }

    public function test_it_eager_loads_relations_when_retrieving_via_timeline()
    {
        $this->signIn();

        $call = $this->factory()->has(Contact::factory())->create();

        $call->comments()->create(['body' => 'Test']);

        $contact = $call->contacts[0];

        $this->getJson("/api/contacts/$contact->id/calls?timeline=1")->assertJsonStructure([
            'data' => [
                ['user', 'comments_count'],
            ],
        ])->assertJsonPath('data.0.comments_count', 1);
    }

    public function test_it_send_notifications_to_mentioned_users_when_call_is_created()
    {
        Notification::fake();

        $user = $this->signIn();

        $mentionUser = $this->createUser();
        $contact = Contact::factory()->create();

        $this->postJson($this->createEndpoint(), array_merge($this->factory()->for($user)->make()->toArray(), [
            'via_resource' => 'contacts',
            'via_resource_id' => $contact->id,
            'body' => 'Other Text - '.$this->mentionText($mentionUser->id, $mentionUser->name),
        ]));

        $call = Call::first();

        Notification::assertSentTo($mentionUser, UserMentioned::class, function (UserMentioned $notification) use ($contact, $call) {
            return $notification->mentionUrl === "/contacts/{$contact->id}?section=calls&resourceId={$call->id}";
        });
    }

    public function test_it_send_notifications_to_mentioned_users_when_call_is_updated()
    {
        Notification::fake();

        $user = $this->signIn();

        $mentionUser = $this->createUser();
        $call = $this->factory()->for($user)->create();
        $contact = Contact::factory()->create();

        $this->putJson($this->updateEndpoint($call), [
            'call_outcome_id' => $call->call_outcome_id,
            'body' => $call->body.$this->mentionText($mentionUser->id, $mentionUser->name),
            'date' => now(),
            'via_resource' => 'contacts',
            'via_resource_id' => $contact->id,
        ]);

        $call->refresh();

        Notification::assertSentTo($mentionUser, UserMentioned::class, function (UserMentioned $notification) use ($contact, $call) {
            return $notification->mentionUrl === "/contacts/{$contact->id}?section=calls&resourceId={$call->id}";
        });
    }
}
