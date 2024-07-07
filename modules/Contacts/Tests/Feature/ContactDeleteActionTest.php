<?php
 

namespace Modules\Contacts\Tests\Feature;

use Modules\Core\Tests\ResourceTestCase;
use Modules\Users\App\Models\User;

class ContactDeleteActionTest extends ResourceTestCase
{
    protected $resourceName = 'contacts';

    public function test_contact_delete_action()
    {
        $this->signIn();

        $contacts = $this->factory()->count(2)->create();

        $this->runAction('delete-action', $contacts)->assertActionOk();
        $this->assertSoftDeleted('contacts', ['id' => $contacts->modelKeys()]);
    }

    public function test_unauthorized_user_cant_run_contact_delete_action()
    {
        $this->asRegularUser()->signIn();

        $contacts = $this->factory()->for(User::factory())->count(2)->create();

        $this->runAction('delete-action', $contacts)->assertActionUnauthorized();
        $this->assertDatabaseHas('contacts', ['id' => $contacts->modelKeys()]);
    }

    public function test_authorized_user_can_run_contact_delete_action()
    {
        $this->asRegularUser()->withPermissionsTo('delete any contact')->signIn();

        $contact = $this->factory()->for(User::factory())->create();

        $this->runAction('delete-action', $contact)->assertActionOk();
        $this->assertSoftDeleted('contacts', ['id' => $contact->id]);
    }

    public function test_authorized_user_can_run_contact_delete_action_only_on_own_contacts()
    {
        $signedInUser = $this->asRegularUser()->withPermissionsTo('delete own contacts')->signIn();

        $contactForSignedIn = $this->factory()->for($signedInUser)->create();
        $othercontact = $this->factory()->create();

        $this->runAction('delete-action', $othercontact)->assertActionUnauthorized();
        $this->assertDatabaseHas('contacts', ['id' => $othercontact->id]);

        $this->runAction('delete-action', $contactForSignedIn);
        $this->assertSoftDeleted('contacts', ['id' => $contactForSignedIn->id]);
    }

    public function test_authorized_user_can_bulk_delete_contacts()
    {
        $this->asRegularUser()->withPermissionsTo([
            'delete any contact', 'bulk delete contacts',
        ])->signIn();

        $contacts = $this->factory()->for(User::factory())->count(2)->create();

        $this->runAction('delete-action', $contacts);
        $this->assertSoftDeleted('contacts', ['id' => $contacts->modelKeys()]);
    }

    public function test_authorized_user_can_bulk_delete_only_own_contacts()
    {
        $signedInUser = $this->asRegularUser()->withPermissionsTo([
            'delete own contacts',
            'bulk delete contacts',
        ])->signIn();

        $contactsForSignedInUser = $this->factory()->count(2)->for($signedInUser)->create();
        $othercontact = $this->factory()->create();

        $this->runAction('delete-action', $contactsForSignedInUser->push($othercontact))->assertActionOk();
        $this->assertDatabaseHas('contacts', ['id' => $othercontact->id]);
        $this->assertSoftDeleted('contacts', ['id' => $contactsForSignedInUser->modelKeys()]);
    }

    public function test_unauthorized_user_cant_bulk_delete_contacts()
    {
        $this->asRegularUser()->signIn();

        $contacts = $this->factory()->count(2)->create();

        $this->runAction('delete-action', $contacts)->assertActionUnauthorized();
        $this->assertDatabaseHas('contacts', ['id' => $contacts->modelKeys()]);
    }

    public function test_user_without_bulk_delete_permission_cannot_bulk_delete_contacts()
    {
        $this->asRegularUser()->withPermissionsTo([
            'delete any contact',
            'delete own contacts',
            'delete team contacts',
        ])->signIn();

        $contacts = $this->factory()->for(User::factory())->count(2)->create();

        $this->runAction('delete-action', $contacts)->assertActionUnauthorized();
        $this->assertDatabaseHas('contacts', ['id' => $contacts->modelKeys()]);
    }
}
