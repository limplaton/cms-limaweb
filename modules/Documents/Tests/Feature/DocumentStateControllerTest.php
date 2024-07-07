<?php
 

namespace Modules\Documents\Tests\Feature;

use Modules\Documents\App\Enums\DocumentStatus;
use Modules\Documents\App\Models\Document;
use Tests\TestCase;

class DocumentStateControllerTest extends TestCase
{
    public function test_user_can_mark_document_as_lost()
    {
        $this->signIn();

        $document = Document::factory()->draft()->create();

        $this->postJson("/api/documents/$document->id/lost")->assertOk()->assertJson([
            'status' => DocumentStatus::LOST->value,
        ]);
    }

    public function test_unauthorized_user_cannot_mark_document_as_lost()
    {
        $this->asRegularUser()->signIn();

        $document = Document::factory()->draft()->create();

        $this->postJson("/api/documents/$document->id/lost")->assertForbidden();
    }

    public function test_authorized_user_can_mark_document_as_lost()
    {
        $this->asRegularUser()->withPermissionsTo(['edit all documents'])->signIn();
        $document = Document::factory()->draft()->create();

        $this->postJson("/api/documents/$document->id/lost")->assertOk();
    }

    public function test_authorized_user_can_mark_own_document_as_lost()
    {
        $user = $this->asRegularUser()->withPermissionsTo(['edit own documents'])->signIn();
        $document = Document::factory()->for($user)->draft()->create();

        $this->postJson("/api/documents/$document->id/lost")->assertOk();
    }

    public function test_it_fails_when_marking_already_lost_document_as_lost()
    {
        $this->signIn();

        $document = Document::factory()->lost()->create();

        $this->postJson("/api/documents/$document->id/lost")->assertStatus(409);
    }

    public function test_it_fails_when_marking_accepted_document_as_lost()
    {
        $this->signIn();

        $document = Document::factory()->accepted()->create();

        $this->postJson("/api/documents/$document->id/lost")->assertStatus(409);
    }

    public function test_user_can_mark_document_as_accepted()
    {
        $this->signIn();

        $document = Document::factory()->draft()->create();

        $this->postJson("/api/documents/$document->id/accept")->assertOk()->assertJson([
            'status' => DocumentStatus::ACCEPTED->value,
        ]);
    }

    public function test_authorized_user_can_mark_document_as_accepted()
    {
        $this->asRegularUser()->withPermissionsTo(['edit all documents'])->signIn();
        $document = Document::factory()->draft()->create();

        $this->postJson("/api/documents/$document->id/accept")->assertOk();
    }

    public function test_authorized_user_can_mark_own_document_as_accepted()
    {
        $user = $this->asRegularUser()->withPermissionsTo(['edit own documents'])->signIn();
        $document = Document::factory()->for($user)->draft()->create();

        $this->postJson("/api/documents/$document->id/accept")->assertOk();
    }

    public function test_it_fails_when_marking_already_accepted_document_as_accepted()
    {
        $this->signIn();

        $document = Document::factory()->accepted()->create();

        $this->postJson("/api/documents/$document->id/accept")->assertStatus(409);
    }

    public function test_user_can_mark_document_as_draft()
    {
        $this->signIn();

        $document = Document::factory()->lost()->create();

        $this->postJson("/api/documents/$document->id/draft")->assertOk()->assertJson([
            'status' => DocumentStatus::DRAFT->value,
        ]);
    }

    public function test_authorized_user_can_mark_document_as_draft()
    {
        $this->asRegularUser()->withPermissionsTo(['edit all documents'])->signIn();
        $document = Document::factory()->lost()->create();

        $this->postJson("/api/documents/$document->id/draft")->assertOk();
    }

    public function test_authorized_user_can_mark_own_document_as_draft()
    {
        $user = $this->asRegularUser()->withPermissionsTo(['edit own documents'])->signIn();
        $document = Document::factory()->for($user)->lost()->create();

        $this->postJson("/api/documents/$document->id/draft")->assertOk();
    }

    public function test_document_accepted_by_customer_cannot_be_marked_as_draft()
    {
        $this->signIn();

        $document = Document::factory()->accepted()->create(['marked_accepted_by' => null]);

        $this->postJson("/api/documents/$document->id/draft")
            ->assertStatus(409)
            ->assertSeeText('Documents signed/accepted by customers cannot be marked as draft.');
    }
}
