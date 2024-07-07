<?php
 

namespace Modules\Documents\Tests\Feature;

use Modules\Core\Tests\ResourceTestCase;
use Modules\Users\App\Models\User;

class DocumentDeleteActionTest extends ResourceTestCase
{
    protected $resourceName = 'documents';

    public function test_document_delete_action()
    {
        $this->signIn();

        $documents = $this->factory()->count(2)->create();

        $this->runAction('delete-action', $documents)->assertActionOk();
        $this->assertSoftDeleted('documents', ['id' => $documents->modelKeys()]);
    }

    public function test_unauthorized_user_cant_run_document_delete_action()
    {
        $this->asRegularUser()->signIn();

        $documents = $this->factory()->for(User::factory())->count(2)->create();

        $this->runAction('delete-action', $documents)->assertActionUnauthorized();
        $this->assertDatabaseHas('documents', ['id' => $documents->modelKeys()]);
    }

    public function test_authorized_user_can_run_document_delete_action()
    {
        $this->asRegularUser()->withPermissionsTo('delete any document')->signIn();

        $document = $this->factory()->for(User::factory())->create();

        $this->runAction('delete-action', $document)->assertActionOk();
        $this->assertSoftDeleted('documents', ['id' => $document->id]);
    }

    public function test_authorized_user_can_run_document_delete_action_only_on_own_documents()
    {
        $signedInUser = $this->asRegularUser()->withPermissionsTo('delete own documents')->signIn();

        $documentForSignedIn = $this->factory()->for($signedInUser)->create();
        $otherdocument = $this->factory()->create();

        $this->runAction('delete-action', $otherdocument)->assertActionUnauthorized();
        $this->assertDatabaseHas('documents', ['id' => $otherdocument->id]);

        $this->runAction('delete-action', $documentForSignedIn);
        $this->assertSoftDeleted('documents', ['id' => $documentForSignedIn->id]);
    }

    public function test_authorized_user_can_bulk_delete_documents()
    {
        $this->asRegularUser()->withPermissionsTo([
            'delete any document', 'bulk delete documents',
        ])->signIn();

        $documents = $this->factory()->for(User::factory())->count(2)->create();

        $this->runAction('delete-action', $documents);
        $this->assertSoftDeleted('documents', ['id' => $documents->modelKeys()]);
    }

    public function test_authorized_user_can_bulk_delete_only_own_documents()
    {
        $signedInUser = $this->asRegularUser()->withPermissionsTo([
            'delete own documents',
            'bulk delete documents',
        ])->signIn();

        $documentsForSignedInUser = $this->factory()->count(2)->for($signedInUser)->create();
        $otherdocument = $this->factory()->create();

        $this->runAction('delete-action', $documentsForSignedInUser->push($otherdocument))->assertActionOk();
        $this->assertDatabaseHas('documents', ['id' => $otherdocument->id]);
        $this->assertSoftDeleted('documents', ['id' => $documentsForSignedInUser->modelKeys()]);
    }

    public function test_unauthorized_user_cant_bulk_delete_documents()
    {
        $this->asRegularUser()->signIn();

        $documents = $this->factory()->count(2)->create();

        $this->runAction('delete-action', $documents)->assertActionUnauthorized();
        $this->assertDatabaseHas('documents', ['id' => $documents->modelKeys()]);
    }

    public function test_user_without_bulk_delete_permission_cannot_bulk_delete_documents()
    {
        $this->asRegularUser()->withPermissionsTo([
            'delete any document',
            'delete own documents',
            'delete team documents',
        ])->signIn();

        $documents = $this->factory()->for(User::factory())->count(2)->create();

        $this->runAction('delete-action', $documents)->assertActionUnauthorized();
        $this->assertDatabaseHas('documents', ['id' => $documents->modelKeys()]);
    }
}
