<?php
 

namespace Modules\Documents\Tests\Feature;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Modules\Documents\App\Enums\DocumentViewType;
use Modules\Documents\App\Models\DocumentTemplate;
use Modules\Users\App\Models\User;
use Tests\TestCase;

class DocumentTemplateModelTest extends TestCase
{
    public function test_document_template_has_user()
    {
        $template = DocumentTemplate::factory()->for(User::factory())->create();

        $this->assertInstanceOf(BelongsTo::class, $template->user());
        $this->assertInstanceOf(User::class, $template->user);
    }

    public function test_document_template_view_type_is_casted()
    {
        $template = DocumentTemplate::factory()->create(['view_type' => DocumentViewType::NAV_LEFT]);

        $this->assertInstanceOf(DocumentViewType::class, $template->view_type);
        $this->assertEquals(DocumentViewType::NAV_LEFT, $template->view_type);
    }

    public function test_document_template_has_used_google_fonts()
    {
        $template = DocumentTemplate::factory()->make();

        $this->assertTrue(method_exists($template, 'usedGoogleFonts'));
        $this->assertInstanceOf(Collection::class, $template->usedGoogleFonts());
    }

    public function test_document_template_can_be_translated_with_custom_group()
    {
        $model = DocumentTemplate::factory()->create(['name' => 'Original']);

        Lang::addLines(['custom.document_template.'.$model->id => 'Changed'], 'en');

        $this->assertSame('Changed', $model->name);
    }

    public function test_document_template_can_be_translated_with_lang_key()
    {
        $model = DocumentTemplate::factory()->create(['name' => 'custom.document_template.some']);

        Lang::addLines(['custom.document_template.some' => 'Changed'], 'en');

        $this->assertSame('Changed', $model->name);
    }

    public function test_it_uses_database_name_when_no_custom_trans_available()
    {
        $model = DocumentTemplate::factory()->create(['name' => 'Database Name']);

        $this->assertSame('Database Name', $model->name);
    }
}
