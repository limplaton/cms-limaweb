<?php
 

namespace Modules\Documents\Tests\Feature;

use Modules\Brands\App\Models\Brand;
use Modules\Contacts\App\Models\Company;
use Modules\Contacts\App\Models\Contact;
use Modules\Core\App\Models\ModelVisibilityGroup;
use Modules\Core\Tests\ResourceTestCase;
use Modules\Deals\App\Models\Deal;
use Modules\Documents\App\Enums\DocumentViewType;
use Modules\Documents\App\Models\Document;
use Modules\Documents\App\Models\DocumentSigner;
use Modules\Documents\App\Models\DocumentType;
use Modules\Users\App\Models\Team;
use Modules\Users\App\Models\User;

class DocumentResourceTest extends ResourceTestCase
{
    protected $resourceName = 'documents';

    public function test_user_can_create_document()
    {
        $this->signIn();

        $user = $this->createUser();
        $brand = Brand::factory()->create();
        $contact = Contact::factory()->create();
        $type = DocumentType::factory()->create();
        $company = Company::factory()->create();
        $deal = Deal::factory()->create();

        $response = $this->postJson($this->createEndpoint(), [
            'title' => 'Proposal Document',
            'brand_id' => $brand->id,
            'document_type_id' => $type->id,
            'view_type' => DocumentViewType::NAV_LEFT->value,
            'requires_signature' => true,
            'signers' => [
                ['name' => 'John Doe', 'email' => 'john@example.com', 'send_email' => true],
            ],
            'recipients' => [
                ['name' => 'Jane Doe', 'email' => 'jane@example.com', 'send_email' => true],
            ],
            'user_id' => $user->id,
            'deals' => [$deal->id],
            'contacts' => [$contact->id],
            'companies' => [$company->id],
        ])
            ->assertCreated();

        $this->assertResourceJsonStructure($response);

        $response->assertJsonCount(1, 'signers')
            ->assertJsonCount(1, 'recipients')
            ->assertJson([

                'signers' => [['name' => 'John Doe', 'email' => 'john@example.com', 'send_email' => true]],
                'recipients' => [['name' => 'Jane Doe', 'email' => 'jane@example.com', 'send_email' => true]],

                'title' => 'Proposal Document',

                'document_type_id' => $type->id,

                'brand_id' => $brand->id,

                'user_id' => $user->id,

                'was_recently_created' => true,
                'display_name' => 'Proposal Document',
            ]);

        $document = Document::first();

        $this->assertCount(1, $document->contacts);
        $this->assertCount(1, $document->companies);
        $this->assertCount(1, $document->deals);
    }

    public function test_user_cant_create_document_with_restricted_visibility_brand()
    {
        $this->asRegularUser()->signIn();

        $brand = $this->newBrandFactoryWithVisibilityGroup('users', User::factory())->create();

        $this
            ->postJson($this->createEndpoint(), ['brand_id' => $brand->id])
            ->assertJsonValidationErrors(['brand_id' => 'This brand id value is forbidden.']);
    }

    public function test_user_cant_update_document_with_restricted_visibility_brand()
    {
        $this->asRegularUser()->withPermissionsTo('edit all documents')->signIn();

        $document = $this->factory()->create();
        $brand = $this->newBrandFactoryWithVisibilityGroup('users', User::factory())->create();

        $this
            ->putJson($this->updateEndpoint($document), ['brand_id' => $brand->id])
            ->assertJsonValidationErrors(['brand_id' => 'This brand id value is forbidden.']);
    }

    public function test_user_can_update_document_with_same_restricted_visibility_brand()
    {
        $this->asRegularUser()->withPermissionsTo(['edit all documents'])->signIn();

        $brand = $this->newBrandFactoryWithVisibilityGroup('users', User::factory())->create();
        $document = $this->factory()->create(['brand_id' => $brand->id]);

        $data = ['brand_id' => $brand->id, 'title' => 'Changed Title'];

        $this
            ->putJson($this->updateEndpoint($document), $data)
            ->assertOk()
            ->assertJson($data);
    }

    public function test_user_cant_create_document_with_restricted_visibility_type()
    {
        $this->asRegularUser()->signIn();

        $document = $this->newDocumentTypeFactoryWithVisilibityGroup('users', User::factory())->create();

        $this
            ->postJson($this->createEndpoint(), ['document_type_id' => $document->id])
            ->assertJsonValidationErrors(['document_type_id' => 'This document type id value is forbidden.']);
    }

    public function test_user_cant_update_document_with_restricted_visibility_type()
    {
        $this->asRegularUser()->withPermissionsTo('edit all documents')->signIn();

        $document = $this->factory()->create();
        $documentType = $this->newDocumentTypeFactoryWithVisilibityGroup('users', User::factory())->create();

        $this
            ->putJson($this->updateEndpoint($document), ['document_type_id' => $documentType->id])
            ->assertJsonValidationErrors(['document_type_id' => 'This document type id value is forbidden.']);
    }

    public function test_user_can_update_document_with_same_restricted_visibility_type()
    {
        $this->asRegularUser()->withPermissionsTo(['edit all documents'])->signIn();

        $type = $this->newDocumentTypeFactoryWithVisilibityGroup('users', User::factory())->create();
        $document = $this->factory()->create(['document_type_id' => $type->id]);

        $data = ['document_type_id' => $type->id, 'title' => 'Changed Title'];

        $this->putJson($this->updateEndpoint($document), $data)
            ->assertOk()
            ->assertJson($data);
    }

    public function test_it_updates_only_signer_send_email_attribute_when_document_is_accepted()
    {
        $user = $this->signIn();

        $brand = Brand::factory()->create();
        $type = DocumentType::factory()->create();

        $document = $this->factory()->accepted()
            ->signable()
            ->has(DocumentSigner::factory(['email' => 'john@example.com', 'send_email' => false]), 'signers')
            ->create();

        $this->putJson($this->updateEndpoint($document), [
            'title' => 'Proposal Document',
            'brand_id' => $brand->id,
            'document_type_id' => $type->id,
            'view_type' => DocumentViewType::NAV_LEFT->value,
            'signers' => [
                ['name' => 'Changed Name', 'email' => 'john@example.com', 'send_email' => true],
            ],
            'user_id' => $user->id,
        ])->assertOk();

        $signer = $document->signers->first();

        $this->assertTrue($signer->send_email);
        $this->assertNotSame('Changed Name', $signer->name);
    }

    public function test_it_doesnt_add_new_signers_when_document_is_accepted()
    {
        $user = $this->signIn();

        $brand = Brand::factory()->create();
        $type = DocumentType::factory()->create();

        $document = $this->factory()->accepted()
            ->signable()
            ->has(DocumentSigner::factory(['email' => 'john@example.com', 'send_email' => false])->signed(), 'signers')
            ->create();

        $this->putJson($this->updateEndpoint($document), [
            'title' => 'Proposal Document',
            'brand_id' => $brand->id,
            'document_type_id' => $type->id,
            'view_type' => DocumentViewType::NAV_LEFT->value,
            'signers' => [
                ['name' => 'Changed Name', 'email' => 'john@example.com', 'send_email' => true],
                ['name' => 'New Name', 'email' => 'new@example.com', 'send_email' => true],
            ],
            'user_id' => $user->id,
        ])->assertOk();

        $this->assertSame(1, $document->signers()->count());
    }

    public function test_it_does_not_delete_all_signers_when_requires_signature_attribute_is_not_provided()
    {
        $user = $this->signIn();

        $brand = Brand::factory()->create();
        $type = DocumentType::factory()->create();

        $document = $this->factory()->accepted()
            ->signable()
            ->has(DocumentSigner::factory(['email' => 'john@example.com', 'send_email' => false])->signed(), 'signers')
            ->create();

        $this->putJson($this->updateEndpoint($document), [
            'title' => 'Proposal Document',
            'brand_id' => $brand->id,
            'document_type_id' => $type->id,
            'view_type' => DocumentViewType::NAV_LEFT->value,
            'user_id' => $user->id,
        ])->assertOk();

        $this->assertSame(1, $document->signers()->count());
    }

    public function test_document_can_be_viewed_without_own_permissions()
    {
        $user = $this->asRegularUser()->signIn();

        $record = $this->factory()->for($user)->create();

        $this->getJson($this->showEndpoint($record))->assertOk()->assertJson(['id' => $record->id]);
    }

    public function test_edit_all_documents_permission()
    {
        $this->asRegularUser()->withPermissionsTo('edit all documents')->signIn();

        $record = $this->factory()->create();

        $this->putJson($this->updateEndpoint($record), $this->samplePayload())->assertOk();
    }

    public function test_edit_own_documents_permission()
    {
        $user = $this->asRegularUser()->withPermissionsTo('edit own documents')->signIn();

        $record1 = $this->factory()->for($user)->create();
        $record2 = $this->factory()->create();

        $this->putJson($this->updateEndpoint($record1), $this->samplePayload())->assertOk();
        $this->putJson($this->updateEndpoint($record2), $this->samplePayload())->assertForbidden();
    }

    public function test_edit_team_documents_permission()
    {
        $user = $this->asRegularUser()->withPermissionsTo('edit team documents')->signIn();

        $teamUser = User::factory()->has(Team::factory()->for($user, 'manager'))->create();

        $record = $this->factory()->for($teamUser)->create();

        $this->putJson($this->updateEndpoint($record))->assertOk();
    }

    public function test_unauthorized_user_cannot_update_document()
    {
        $this->asRegularUser()->signIn();

        $record = $this->factory()->create();

        $this->putJson($this->updateEndpoint($record), $this->samplePayload())->assertForbidden();
    }

    public function test_view_all_documents_permission()
    {
        $this->asRegularUser()->withPermissionsTo('view all documents')->signIn();

        $record = $this->factory()->create();

        $this->getJson($this->showEndpoint($record))->assertOk();
    }

    public function test_view_team_documents_permission()
    {
        $user = $this->asRegularUser()->withPermissionsTo('view team documents')->signIn();

        $teamUser = User::factory()->has(Team::factory()->for($user, 'manager'))->create();

        $record = $this->factory()->for($teamUser)->create();

        $this->getJson($this->showEndpoint($record))->assertOk();
    }

    public function test_user_can_view_own_document()
    {
        $user = $this->asRegularUser()->signIn();

        $record = $this->factory()->for($user)->create();

        $this->getJson($this->showEndpoint($record))->assertOk();
    }

    public function test_unauthorized_user_cannot_view_document()
    {
        $this->asRegularUser()->signIn();

        $record = $this->factory()->create();

        $this->getJson($this->showEndpoint($record))->assertForbidden();
    }

    public function test_delete_any_document_permission()
    {
        $this->asRegularUser()->withPermissionsTo('delete any document')->signIn();

        $record = $this->factory()->create();

        $this->deleteJson($this->deleteEndpoint($record))->assertNoContent();
    }

    public function test_delete_own_documents_permission()
    {
        $user = $this->asRegularUser()->withPermissionsTo('delete own documents')->signIn();

        $record1 = $this->factory()->for($user)->create();
        $record2 = $this->factory()->create();

        $this->deleteJson($this->deleteEndpoint($record1))->assertNoContent();
        $this->deleteJson($this->deleteEndpoint($record2))->assertForbidden();
    }

    public function test_delete_team_documents_permission()
    {
        $user = $this->asRegularUser()->withPermissionsTo('delete team documents')->signIn();

        $teamUser = User::factory()->has(Team::factory()->for($user, 'manager'))->create();

        $record1 = $this->factory()->for($teamUser)->create();
        $record2 = $this->factory()->create();

        $this->deleteJson($this->deleteEndpoint($record1))->assertNoContent();
        $this->deleteJson($this->deleteEndpoint($record2))->assertForbidden();
    }

    public function test_unauthorized_user_cannot_delete_document()
    {
        $this->asRegularUser()->signIn();

        $record = $this->factory()->create();

        $this->deleteJson($this->showEndpoint($record))->assertForbidden();
    }

    public function test_it_empties_documents_trash()
    {
        $this->signIn();

        $this->factory()->count(2)->trashed()->create();

        $this->deleteJson('/api/trashed/documents?limit=2')->assertJson(['deleted' => 2]);
        $this->assertDatabaseEmpty('documents');
    }

    public function test_it_excludes_unauthorized_records_from_empty_documents_trash()
    {
        $user = $this->asRegularUser()->withPermissionsTo(['view own documents', 'delete own documents', 'bulk delete documents'])->signIn();

        $this->factory()->trashed()->create();
        $this->factory()->trashed()->for($user)->create();

        $this->deleteJson('/api/trashed/documents')->assertJson(['deleted' => 1]);
        $this->assertDatabaseCount('documents', 1);
    }

    public function test_it_does_not_empty_documents_trash_if_delete_own_documents_permission_is_not_applied()
    {
        $user = $this->asRegularUser()->withPermissionsTo(['view own documents', 'bulk delete documents'])->signIn();

        $this->factory()->trashed()->for($user)->create();

        $this->deleteJson('/api/trashed/documents')->assertJson(['deleted' => 0]);
        $this->assertDatabaseCount('documents', 1);
    }

    protected function samplePayload()
    {
        $brand = Brand::factory()->create();
        $type = DocumentType::factory()->create();
        $user = User::factory()->create();

        return [
            'title' => 'Proposal Document',
            'brand_id' => $brand->id,
            'document_type_id' => $type->id,
            'view_type' => DocumentViewType::NAV_LEFT->value,
            'user_id' => $user->id,
        ];
    }

    protected function newBrandFactoryWithVisibilityGroup($group, $attached)
    {
        return Brand::factory()->has(
            ModelVisibilityGroup::factory()->{$group}()->hasAttached($attached),
            'visibilityGroup'
        );
    }

    protected function newDocumentTypeFactoryWithVisilibityGroup($group, $attached)
    {
        return DocumentType::factory()->has(
            ModelVisibilityGroup::factory()->{$group}()->hasAttached($attached),
            'visibilityGroup'
        );
    }

    protected function assertResourceJsonStructure($response)
    {
        $response->assertJsonStructure([
            'actions', 'accepted_at', 'amount', 'associations_count', 'authorizations', 'billable', 'brand_id', 'changelog', 'content', 'created_at', 'created_by', 'display_name', 'document_type_id', 'google_fonts', 'id', 'last_date_sent', 'marked_accepted_by', 'original_date_sent', 'owner_assigned_date', 'path', 'public_url', 'recipients', 'requires_signature', 'send_at', 'send_mail_account_id', 'send_mail_body', 'send_mail_subject', 'signers', 'status', 'timeline_component', 'timeline_relation', 'title', 'type', 'updated_at', 'user', 'user_id', 'view_type', 'was_recently_created',
        ]);
    }
}
