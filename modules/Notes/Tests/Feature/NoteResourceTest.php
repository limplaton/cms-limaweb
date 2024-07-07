<?php
 

namespace Modules\Notes\Tests\Feature;

use Illuminate\Support\Facades\Notification;
use Modules\Contacts\App\Models\Contact;
use Modules\Core\Tests\ResourceTestCase;
use Modules\Notes\App\Models\Note;
use Modules\Users\App\Notifications\UserMentioned;
use Modules\Users\Tests\Concerns\TestsMentions;

class NoteResourceTest extends ResourceTestCase
{
    use TestsMentions;

    protected $resourceName = 'notes';

    public function test_user_can_create_resource_record()
    {
        $this->signIn();

        $contact = Contact::factory()->create();

        $this->postJson($this->createEndpoint(), [
            'body' => 'Note Body',
            'via_resource' => 'contacts',
            'via_resource_id' => $contact->id,
            'contacts' => [$contact->id],
        ])->assertCreated()->assertJson([
            'body' => 'Note Body',
        ]);

        $this->assertCount(1, Note::first()->contacts);
    }

    public function test_user_can_create_resource_record_with_associations_attribute()
    {
        $this->signIn();

        $contact = Contact::factory()->create();

        $this->postJson($this->createEndpoint(), [
            'body' => 'Note Body',
            'via_resource' => 'contacts',
            'via_resource_id' => $contact->id,
            'associations' => [
                'contacts' => [$contact->id],
            ],
        ])->assertCreated();

        $this->assertCount(1, Note::first()->contacts);
    }

    public function test_user_can_update_resource_record()
    {
        $this->signIn();
        $note = $this->factory()->create();
        $contact = Contact::factory()->create();

        $this->putJson($this->updateEndpoint($note), [
            'body' => 'Updated Body',
            'via_resource' => 'contacts',
            'via_resource_id' => $contact->id,
        ])->assertOk()->assertJson([
            'body' => 'Updated Body',
        ]);
    }

    public function test_user_can_update_only_own_created_note()
    {
        $user = $this->asRegularUser()->createUser();
        $contact = Contact::factory()->create();
        $this->signIn($user);
        $user2 = $this->createUser();
        $note = $this->factory()->for($user2)->create();

        $this->putJson($this->updateEndpoint($note), [
            'body' => 'Updated Body',
            'via_resource' => 'contacts',
            'via_resource_id' => $contact->id,
        ])->assertForbidden();
    }

    public function test_note_requires_body()
    {
        $this->signIn();
        $note = $this->factory()->create();

        $this->postJson($this->createEndpoint(), [
            'body' => '',
        ])->assertJsonValidationErrorFor('body');

        $this->putJson($this->updateEndpoint($note), [
            'body' => '',
        ])->assertJsonValidationErrorFor('body');
    }

    public function test_note_requires_via_resource()
    {
        $this->signIn();
        $note = $this->factory()->create();
        $contact = Contact::factory()->create();

        $this->postJson($this->createEndpoint(), [
            'body' => 'Note Body',
            'via_resource_id' => $contact->id,
            'via_resource' => '',
        ])->assertJsonValidationErrorFor('via_resource');
        $this->putJson($this->updateEndpoint($note), [
            'body' => 'Note Body',
            'via_resource_id' => $contact->id,
            'via_resource' => '',
        ])->assertJsonValidationErrorFor('via_resource');
    }

    public function test_note_requires_via_resource_id()
    {
        $this->signIn();
        $note = $this->factory()->create();

        $this->postJson($this->createEndpoint(), [
            'body' => 'Note Body',
            'via_resource' => 'contacts',
            'via_resource_id' => '',
        ])->assertJsonValidationErrorFor('via_resource_id');

        $this->putJson($this->updateEndpoint($note), [
            'body' => 'Note Body',
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

    public function test_user_can_retrieve_notes_that_are_associated_with_related_records_the_user_is_authorized_to_see()
    {
        $user = $this->asRegularUser()->withPermissionsTo('view own contacts')->createUser();
        $this->signIn($user);
        $user2 = $this->createUser();
        $this->factory()->create();
        $this->factory()->for($user2)->create();
        $this->factory()->for($user)->has(Contact::factory()->for($user))->create();

        $this->getJson($this->indexEndpoint())->assertJsonCount(1, 'data');
    }

    public function test_user_can_retrieve_note_associated_with_related_record_the_user_is_authorized_to_see()
    {
        $user = $this->asRegularUser()->withPermissionsTo('view own contacts')->createUser();
        $this->signIn($user);
        $otherUser = $this->createUser();
        $note = $this->factory()->for($otherUser)->has(Contact::factory()->for($user))->create();

        $endpoint = $this->showEndpoint($note).'?via_resource=contacts&via_resource_id='.$note->contacts->first()->getKey();

        $this->getJson($endpoint)->assertOk();
    }

    public function test_user_can_retrieve_resource_record()
    {
        $this->signIn();

        $record = $this->factory()->create();

        $this->getJson($this->showEndpoint($record))->assertOk();
    }

    public function test_user_can_retrieve_only_own_created_note()
    {
        $user = $this->asRegularUser()->createUser();
        $this->signIn($user);
        $user2 = $this->createUser();
        $note = $this->factory()->for($user2)->create();

        $this->getJson($this->showEndpoint($note))->assertForbidden();
    }

    public function test_user_can_delete_resource_record()
    {
        $this->signIn();

        $record = $this->factory()->create();

        $this->deleteJson($this->deleteEndpoint($record))->assertNoContent();
    }

    public function test_user_can_delete_only_own_created_note()
    {
        $user = $this->asRegularUser()->createUser();
        $this->signIn($user);
        $user2 = $this->createUser();
        $note = $this->factory()->for($user2)->create();

        $this->deleteJson($this->deleteEndpoint($note))->assertForbidden();
    }

    public function test_it_eager_loads_relations_when_retrieving_via_associated_record()
    {
        $this->signIn();

        $note = $this->factory()->has(Contact::factory())->create();

        $note->comments()->create(['body' => 'Test']);

        $contact = $note->contacts[0];

        $this->getJson("/api/contacts/$contact->id/notes")->assertJsonStructure([
            'data' => [
                ['user', 'comments_count'],
            ],
        ])->assertJsonPath('data.0.comments_count', 1);
    }

    public function test_it_eager_loads_relations_when_retrieving_via_timeline()
    {
        $this->signIn();

        $note = $this->factory()->has(Contact::factory())->create();

        $note->comments()->create(['body' => 'Test']);

        $contact = $note->contacts[0];

        $this->getJson("/api/contacts/$contact->id/notes?timeline=1")->assertJsonStructure([
            'data' => [
                ['user', 'comments_count'],
            ],
        ])->assertJsonPath('data.0.comments_count', 1);
    }

    public function test_user_can_retrieve_notes_related_to_associations_authorized_to_view()
    {
        $user = $this->asRegularUser()->withPermissionsTo('view own contacts')->signIn();
        $note = $this->factory()->has(Contact::factory()->for($user))->create();
        $contact = $note->contacts[0];

        $this->getJson("/api/notes/$note->id?via_resource=contacts&via_resource_id=$contact->id")->assertOk();
    }

    public function test_it_send_notifications_to_mentioned_users_when_note_is_created()
    {
        Notification::fake();

        $this->signIn();

        $mentionUser = $this->createUser();
        $contact = Contact::factory()->create();

        $this->postJson($this->createEndpoint(), [
            'via_resource' => 'contacts',
            'via_resource_id' => $contact->id,
            'body' => 'Other Text - '.$this->mentionText($mentionUser->id, $mentionUser->name),
        ]);

        $note = Note::first();

        Notification::assertSentTo($mentionUser, UserMentioned::class, function (UserMentioned $notification) use ($contact, $note) {
            return $notification->mentionUrl === "/contacts/{$contact->id}?section=notes&resourceId={$note->id}";
        });
    }

    public function test_it_send_notifications_to_mentioned_users_when_note_is_updated()
    {
        Notification::fake();

        $user = $this->signIn();

        $mentionUser = $this->createUser();
        $note = $this->factory()->for($user)->create();
        $contact = Contact::factory()->create();

        $this->putJson($this->updateEndpoint($note), [
            'body' => $note->body.$this->mentionText($mentionUser->id, $mentionUser->name),
            'via_resource' => 'contacts',
            'via_resource_id' => $contact->id,
        ]);

        $note->refresh();

        Notification::assertSentTo($mentionUser, UserMentioned::class, function (UserMentioned $notification) use ($contact, $note) {
            return $notification->mentionUrl === "/contacts/{$contact->id}?section=notes&resourceId={$note->id}";
        });
    }
}
