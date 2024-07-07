<?php
 

namespace Modules\Documents\Tests\Feature;

use Barryvdh\DomPDF\PDF;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Modules\Brands\App\Models\Brand;
use Modules\Contacts\App\Models\Company;
use Modules\Contacts\App\Models\Contact;
use Modules\Deals\App\Models\Deal;
use Modules\Documents\App\Content\DocumentContent;
use Modules\Documents\App\Models\Document;
use Modules\Documents\App\Models\DocumentSigner;
use Modules\Documents\App\Models\DocumentType;
use Modules\Users\App\Models\User;
use Tests\TestCase;

class DocumentModelTest extends TestCase
{
    public function test_document_has_signers()
    {
        $document = Document::factory()->has(DocumentSigner::factory(), 'signers')->create();

        $this->assertInstanceOf(HasMany::class, $document->signers());
        $this->assertInstanceOf(Collection::class, $document->signers);
        $this->assertCount(1, $document->signers);
        $this->assertInstanceOf(DocumentSigner::class, $document->signers->first());
    }

    public function test_document_has_companies()
    {
        $document = Document::factory()->has(Company::factory())->create();

        $this->assertInstanceOf(MorphToMany::class, $document->companies());
        $this->assertInstanceOf(Collection::class, $document->companies);
        $this->assertCount(1, $document->companies);
        $this->assertInstanceOf(Company::class, $document->companies->first());
    }

    public function test_document_has_contacts()
    {
        $document = Document::factory()->has(Contact::factory())->create();

        $this->assertInstanceOf(MorphToMany::class, $document->contacts());
        $this->assertInstanceOf(Collection::class, $document->contacts);
        $this->assertCount(1, $document->contacts);
        $this->assertInstanceOf(Contact::class, $document->contacts->first());
    }

    public function test_document_has_deals()
    {
        $document = Document::factory()->has(Deal::factory())->create();

        $this->assertInstanceOf(MorphToMany::class, $document->deals());
        $this->assertInstanceOf(Collection::class, $document->deals);
        $this->assertCount(1, $document->deals);
        $this->assertInstanceOf(Deal::class, $document->deals->first());
    }

    public function test_document_has_type()
    {
        $document = Document::factory()->for(DocumentType::factory(), 'type')->create();

        $this->assertInstanceOf(BelongsTo::class, $document->type());
        $this->assertInstanceOf(DocumentType::class, $document->type);
    }

    public function test_document_has_user()
    {
        $document = Document::factory()->for(User::factory())->create();

        $this->assertInstanceOf(BelongsTo::class, $document->user());
        $this->assertInstanceOf(User::class, $document->user);
    }

    public function test_document_has_brand()
    {
        $document = Document::factory()->for(Brand::factory())->create();

        $this->assertInstanceOf(BelongsTo::class, $document->brand());
        $this->assertInstanceOf(Brand::class, $document->brand);
    }

    public function test_document_has_pdf()
    {
        $document = Document::factory()->create();

        $this->assertInstanceOf(PDF::class, $document->pdf());
    }

    public function test_document_has_path()
    {
        $document = Document::factory()->create();

        $this->assertEquals('/documents/'.$document->id, $document->path());
    }

    public function test_document_has_display_name_attribute()
    {
        $document = Document::factory(['title' => 'Document title'])->make();

        $this->assertEquals('Document title', $document->displayName());
    }

    public function test_document_has_content_attribute()
    {
        $document = Document::factory()->make();

        $this->assertInstanceOf(DocumentContent::class, $document->content);
    }

    public function test_document_has_public_url_attribute()
    {
        $document = Document::factory()->create();

        $this->assertSame(url("/d/$document->uuid"), $document->publicUrl);
    }

    public function test_document_content_is_provided_as_pending_media_attribute()
    {
        $document = Document::factory()->make();

        $this->assertTrue(method_exists($document, 'textAttributesWithMedia'));
        $this->assertEquals('content', $document->textAttributesWithMedia());
    }

    public function test_document_provides_total_column_for_billable()
    {
        $document = Document::factory()->make();

        $this->assertTrue(method_exists($document, 'totalColumn'));
        $this->assertEquals('amount', $document->totalColumn());
    }

    public function test_created_activity_is_logged_when_document_is_created()
    {
        $document = Document::factory()->create();

        $this->assertDatabaseHas(config('activitylog.table_name'), [
            'subject_type' => Document::class,
            'subject_id' => $document->id,
        ]);
    }

    public function test_it_can_determine_if_all_signers_signed_the_document()
    {
        $document = Document::factory()->has(DocumentSigner::factory()->signed()->count(2), 'signers')->create();

        $this->assertTrue($document->everyoneSigned());

        $document = Document::factory()
            ->has(DocumentSigner::factory()->signed(), 'signers')
            ->has(DocumentSigner::factory(), 'signers')
            ->create();

        $this->assertFalse($document->everyoneSigned());
    }

    public function test_it_can_determine_if_at_least_one_signer_signed_the_document()
    {
        $document = Document::factory()
            ->has(DocumentSigner::factory()->signed(), 'signers')
            ->has(DocumentSigner::factory(), 'signers')
            ->create();

        $this->assertTrue($document->atLeastOneSigned());
        $this->assertFalse($document->everyoneSigned());
    }
}
