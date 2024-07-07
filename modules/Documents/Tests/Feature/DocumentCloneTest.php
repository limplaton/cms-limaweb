<?php
 

namespace Modules\Documents\Tests\Feature;

use Modules\Billable\App\Models\Billable;
use Modules\Billable\App\Models\BillableProduct;
use Modules\Contacts\App\Models\Company;
use Modules\Contacts\App\Models\Contact;
use Modules\Deals\App\Models\Deal;
use Modules\Documents\App\Enums\DocumentStatus;
use Modules\Documents\App\Models\Document;
use Modules\Documents\App\Models\DocumentSigner;
use Tests\TestCase;

class DocumentCloneTest extends TestCase
{
    public function test_document_can_be_cloned()
    {
        $user = $this->signIn();

        $documentFactory = Document::factory([
            'send_at' => '2023-03-21 12:05:00',
        ])
            ->sent()
            ->has(DocumentSigner::factory()->mailable(), 'signers')
            ->has(Deal::factory())
            ->has(Contact::factory())
            ->has(Company::factory())
            ->hasRecipients([
                ['name' => 'Jane Doe', 'email' => 'jane@example.com', 'send_email' => true],
            ]);

        $billable = Billable::factory()
            ->withBillableable($documentFactory)
            ->has(BillableProduct::factory(2), 'products')
            ->create();

        $document = $billable->billableable;

        $this->postJson("/api/documents/$document->id/clone")
            ->assertOk()
            ->assertJson([
                'status' => DocumentStatus::DRAFT->value,
                'user_id' => $user->id,
                'created_by' => $user->id,
                'send_at' => null,
            ])
            ->assertJsonCount(1, 'recipients')
            ->assertJsonPath('recipients.0.send_email', true)
            ->assertJsonCount(1, 'signers')
            ->assertJsonPath('signers.0.send_email', true)
            ->assertJsonCount(2, 'billable.products')
            ->assertJsonPath('billable.tax_type', $billable->tax_type->name);

        $this->assertCount(1, $document->contacts);
        $this->assertCount(1, $document->companies);
        $this->assertCount(1, $document->deals);
    }

    public function test_it_clears_accepted_attributes_on_document_clone()
    {
        $user = $this->signIn();

        $document = Document::factory()->accepted()->create([
            'marked_accepted_by' => $user->id,
        ]);

        $this->postJson("/api/documents/$document->id/clone")
            ->assertOk()
            ->assertJson([
                'accepted_at' => null,
                'marked_accepted_by' => null,
            ]);
    }

    public function test_it_clears_mail_attributes_on_document_clone()
    {
        $user = $this->signIn();

        $document = Document::factory()->sent()->create([
            'send_at' => now(),
            'sent_by' => $user->id,
        ]);

        $id = $this->postJson("/api/documents/$document->id/clone")
            ->assertOk()
            ->assertJson([
                'send_at' => null,
                'original_date_sent' => null,
                'last_date_sent' => null,
            ])->getData()->id;

        $this->assertNull(Document::find($id)->sent_by);
    }

    public function test_it_clears_signers_signature_on_document_clone()
    {
        $this->signIn();

        $document = Document::factory()->has(DocumentSigner::factory()->signed(), 'signers')->create();

        $this->postJson("/api/documents/$document->id/clone")
            ->assertOk()
            ->assertJsonPath('signers.0.signature', null)
            ->assertJsonPath('signers.0.signed_at', null)
            ->assertJsonPath('signers.0.sign_ip', null);
    }
}
