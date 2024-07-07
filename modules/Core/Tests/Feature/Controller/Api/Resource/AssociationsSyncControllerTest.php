<?php
 

namespace Modules\Core\Tests\Feature\Controller\Api\Resource;

use Modules\Contacts\App\Models\Company;
use Modules\Contacts\App\Models\Contact;
use Modules\Deals\App\Models\Deal;
use Tests\TestCase;

class AssociationsSyncControllerTest extends TestCase
{
    public function test_user_can_attach_associations_to_resource()
    {
        $this->signIn();

        $contact = Contact::factory()->create();
        $company = Company::factory()->create();

        $this->putJson('/api/associations/contacts/'.$contact->id, [
            'companies' => [$company->id],
        ])->assertOk();

        $this->assertCount(1, $contact->companies);
    }

    public function test_unauthorized_user_to_view_the_associations_cannot_attach_them_to_the_resource()
    {
        $user = $this->asRegularUser()->withPermissionsTo(['view own contacts', 'edit own contacts'])->signIn();
        $anotherUser = $this->createUser();
        $contact = Contact::factory()->for($user)->create();
        $company = Company::factory()->for($anotherUser)->create();

        $this->putJson('/api/associations/contacts/'.$contact->id, [
            'companies' => [$company->id],
        ])->assertForbidden();

        $this->assertCount(0, $contact->companies);
    }

    public function test_it_shows_a_message_when_attaching_and_only_one_resource_is_provided_and_this_resource_is_already_attached()
    {
        $this->signIn();

        $contact = Contact::factory()->has(Deal::factory())->create();

        $this->putJson('/api/associations/contacts/'.$contact->id, [
            'deals' => [$contact->deals->first()->id],
        ])->assertStatus(409);
    }

    public function test_when_attaching_it_does_not_do_anything_if_the_provided_resource_name_is_not_array()
    {
        $this->signIn();

        $contact = Contact::factory()->create();
        $company = Company::factory()->create();

        $this->putJson('/api/associations/contacts/'.$contact->id, [
            'companies' => [$company->id],
            'deals' => null,
        ])->assertOk();
    }

    public function test_user_can_synchronize_associations_to_resource()
    {
        $this->signIn();

        $contact = Contact::factory()->has(Deal::factory())->create();
        $company = Company::factory()->create();
        $newDeal = Deal::factory()->create();

        $this->postJson('/api/associations/contacts/'.$contact->id, [
            'companies' => [$company->id],
            'deals' => [$newDeal->id],
        ])->assertOk();

        $this->assertCount(1, $contact->companies);
        $this->assertCount(1, $contact->deals);
        $this->assertEquals($newDeal->id, $contact->deals->first()->id);
    }

    public function test_it_detaches_all_when_the_provided_no_assications_provided_when_syncing()
    {
        $this->signIn();

        $contact = Contact::factory()->has(Deal::factory())->create();

        $this->postJson('/api/associations/contacts/'.$contact->id, [
            'deals' => [],
        ])->assertOk();

        $this->assertCount(0, $contact->deals);
    }

    public function test_when_synchronizing_it_does_not_do_anything_if_the_provided_resource_name_is_not_array()
    {
        $this->signIn();

        $contact = Contact::factory()->has(Deal::factory())->create();
        $company = Company::factory()->create();

        $this->postJson('/api/associations/contacts/'.$contact->id, [
            'companies' => [$company->id],
            'deals' => null,
        ])->assertOk();
    }

    public function test_user_can_detach_associations_from_resource()
    {
        $this->signIn();

        $contact = Contact::factory()->has(Company::factory())->create();

        $this
            ->deleteJson('/api/associations/contacts/'.$contact->id, [
                'companies' => $contact->companies->modelKeys(),
            ])
            ->assertOk()
            ->assertJson(['id' => $contact->id]);

        $this->assertCount(0, $contact->companies()->get());
    }

    public function test_unauthorized_user_to_view_the_associations_cannot_detach_from_to_the_resource()
    {
        $user = $this->asRegularUser()->withPermissionsTo(['view own contacts', 'edit own contacts'])->signIn();
        $anotherUser = $this->createUser();
        $contact = Contact::factory()->has(Company::factory()->for($anotherUser))->for($user)->create();

        $this
            ->deleteJson('/api/associations/contacts/'.$contact->id, [
                'companies' => $contact->companies->modelKeys(),
            ])
            ->assertOk()
            ->assertJson(['id' => $contact->id]);

        $this->assertCount(1, $contact->companies);
    }

    public function test_when_detaching_it_does_not_do_anything_if_the_provided_resource_name_is_not_array()
    {
        $this->signIn();

        $contact = Contact::factory()->has(Company::factory())->create();

        $this
            ->deleteJson('/api/associations/contacts/'.$contact->id, [
                'companies' => $contact->companies->modelKeys(),
                'deals' => null,
            ])
            ->assertOk()
            ->assertJson(['id' => $contact->id]);
    }

    public function test_it_validates_associatebles_resources()
    {
        $this->signIn();

        $contact = Contact::factory()->has(Company::factory())->create();

        $this->deleteJson('/api/associations/contacts/'.$contact->id, [
            'calendars' => [],
        ])->assertStatus(409);

        $this->postJson('/api/associations/contacts/'.$contact->id, [
            'calendars' => [],
        ])->assertStatus(409);

        $this->putJson('/api/associations/contacts/'.$contact->id, [
            'calendars' => [],
        ])->assertStatus(409);
    }
}
