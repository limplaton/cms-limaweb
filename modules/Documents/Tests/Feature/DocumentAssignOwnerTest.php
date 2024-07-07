<?php
 

namespace Modules\Documents\Tests\Feature;

use Modules\Core\Tests\ResourceTestCase;

class DocumentAssignOwnerTest extends ResourceTestCase
{
    protected $action = 'assign-owner-action';

    protected $resourceName = 'documents';

    public function test_super_admin_user_can_run_document_assign_owner_action()
    {
        $this->signIn();

        $user = $this->createUser();
        $document = $this->factory()->create();

        $this->runAction($this->action, $document, ['user_id' => $user->id])->assertActionOk();
        $this->assertEquals($user->id, $document->fresh()->user_id);
    }

    public function test_authorized_user_can_run_document_assign_owner_action()
    {
        $this->asRegularUser()->withPermissionsTo('edit all documents')->signIn();

        $user = $this->createUser();
        $document = $this->factory()->for($user)->create();

        $this->runAction($this->action, $document, ['user_id' => $user->id])->assertActionOk();
        $this->assertEquals($user->id, $document->fresh()->user_id);
    }

    public function test_unauthorized_user_can_run_document_assign_owner_action_on_own_document()
    {
        $signedInUser = $this->asRegularUser()->withPermissionsTo('edit own documents')->signIn();
        $user = $this->createUser();

        $documentForSignedIn = $this->factory()->for($signedInUser)->create();
        $otherDocument = $this->factory()->create();

        $this->runAction($this->action, $otherDocument, ['user_id' => $user->id])->assertActionUnauthorized();
        $this->runAction($this->action, $documentForSignedIn, ['user_id' => $user->id])->assertActionOk();
        $this->assertEquals($user->id, $documentForSignedIn->fresh()->user_id);
    }

    public function test_document_assign_owner_action_requires_owner()
    {
        $this->signIn();

        $this->runAction($this->action, [])->assertJsonValidationErrors(['user_id']);
    }
}
