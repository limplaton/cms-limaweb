<?php
 

namespace Modules\Documents\Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Modules\Documents\App\Enums\DocumentStatus;
use Modules\Documents\App\Mail\DocumentAccepted as MailDocumentAccepted;
use Modules\Documents\App\Mail\DocumentSignedThankYouMessage;
use Modules\Documents\App\Mail\SignerSignedDocument as MailSignerSignedDocument;
use Modules\Documents\App\Models\Document;
use Modules\Documents\App\Models\DocumentSigner;
use Modules\Documents\App\Notifications\DocumentAccepted;
use Modules\Documents\App\Notifications\SignerSignedDocument;
use Tests\TestCase;

class DocumentAcceptControllerTest extends TestCase
{
    public function test_document_can_be_accepted()
    {
        Notification::fake();

        $document = Document::factory()->draft()->create();

        $this->postJson("/api/d/$document->uuid/accept")->assertNoContent();

        $document->refresh();

        $this->assertEquals(DocumentStatus::ACCEPTED, $document->status);
        $this->assertNotNull($document->accepted_at);

        Notification::assertSentTimes(DocumentAccepted::class, 1);
        Notification::assertSentTo(
            $document->user,
            DocumentAccepted::class,
            function (DocumentAccepted $notification, array $channels) use ($document) {
                return in_array('mail', $channels) &&
                $notification->toMail($document->user) instanceof MailDocumentAccepted &&
                $notification->toArray($document->user) === [
                    'path' => $document->path(),
                    'lang' => [
                        'key' => 'documents::document.notifications.accepted',
                        'attrs' => [
                            'title' => $document->title,
                        ],
                    ],
                ];
            });
    }

    public function test_cannot_accept_already_accepted_document()
    {
        $document = Document::factory()->accepted()->create();

        $this->postJson("/api/d/$document->uuid/accept")->assertNotFound();
    }

    public function test_document_can_be_signed()
    {
        Notification::fake();
        Mail::fake();

        $document = Document::factory()
            ->signable()
            ->sent()
            ->has(DocumentSigner::factory(), 'signers')
            ->create();

        $this->postJson("/api/d/$document->uuid/sign", [
            'email' => $email = $document->signers[0]->email,
            'signature' => $document->signers[0]['name'],
        ])->assertNoContent();

        $document->refresh();

        $this->assertEquals(DocumentStatus::ACCEPTED, $document->status);

        Notification::assertSentTo(
            $document->user,
            SignerSignedDocument::class,
            function (SignerSignedDocument $notification, array $channels) use ($document) {
                return in_array('mail', $channels) &&
                    $notification->toMail($document->user) instanceof MailSignerSignedDocument &&
                    $notification->toArray($document->user) === [
                        'path' => $document->path(),
                        'lang' => [
                            'key' => 'documents::document.notifications.signed',
                            'attrs' => [
                                'title' => $document->title,
                            ],
                        ],
                    ];
            });

        Mail::assertSent(DocumentSignedThankYouMessage::class, function (DocumentSignedThankYouMessage $mail) use ($email) {
            return $mail->hasTo($email);
        });
    }

    public function test_cannot_sign_already_signed_document()
    {
        $document = Document::factory()
            ->signable()
            ->accepted()->has(DocumentSigner::factory()->signed(), 'signers')
            ->create();

        $this->postJson("/api/d/$document->uuid/sign", [
            'email' => $document->signers[0]->email,
            'signature' => $document->signers[0]['name'],
        ])->assertNotFound();
    }

    public function test_sign_requires_valid_email_address()
    {
        $document = Document::factory()->create();

        $this->postJson("/api/d/$document->uuid/sign", [
            'email' => 'invalid-email',
        ])->assertJsonValidationErrorFor('email');
    }

    public function test_sign_requires_signature()
    {
        $document = Document::factory()->create();

        $this->postJson("/api/d/$document->uuid/sign", [
            'signature' => '',
        ])->assertJsonValidationErrorFor('signature');
    }

    public function test_it_can_confirm_the_signer_email_address()
    {
        $document = Document::factory()
            ->signable()
            ->draft()->has(DocumentSigner::factory()->signed(), 'signers')
            ->create();

        $this->postJson("/api/d/$document->uuid/validate", [
            'email' => $document->signers[0]->email,
        ])
            ->assertOk()
            ->assertJson(['name' => $document->signers[0]->name]);
    }

    public function test_it_does_not_confirm_signer_when_no_email_provided()
    {
        $document = Document::factory()->create();

        $this->postJson("/api/d/$document->uuid/validate", [
            'email' => '',
        ])->assertNoContent();
    }

    public function test_it_does_not_confirm_signer_when_no_signer_not_exists()
    {
        $document = Document::factory()->has(DocumentSigner::factory(), 'signers')->create();

        $this->postJson("/api/d/$document->uuid/validate", [
            'email' => 'unknown@example.com',
        ])->assertNoContent();
    }

    public function test_it_fails_when_document_is_not_found_by_the_provided_uuid()
    {
        $this->postJson('/api/d/unknown/sign')->assertNotFound();
        $this->postJson('/api/d/unknown/accept')->assertNotFound();
        $this->postJson('/api/d/unknown/validate', ['email' => 'email@example.com'])->assertNotFound();
    }
}
