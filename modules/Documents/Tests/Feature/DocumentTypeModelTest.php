<?php
 

namespace Modules\Documents\Tests\Feature;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Lang;
use Modules\Documents\App\Models\DocumentType;
use Tests\TestCase;

class DocumentTypeModelTest extends TestCase
{
    public function test_type_has_document()
    {
        $type = DocumentType::factory()->make();

        $this->assertInstanceOf(HasMany::class, $type->documents());
    }

    public function test_document_type_can_be_primary()
    {
        $type = DocumentType::factory()->primary()->create();

        $this->assertTrue($type->isPrimary());

        $type->flag = null;
        $type->save();

        $this->assertFalse($type->isPrimary());
    }

    public function test_document_type_can_be_default()
    {
        $type = DocumentType::factory()->primary()->create();

        DocumentType::setDefault($type->id);

        $this->assertEquals($type->id, DocumentType::getDefaultType());
    }

    public function test_document_type_can_be_translated_with_custom_group()
    {
        $model = DocumentType::factory()->create(['name' => 'Original']);

        Lang::addLines(['custom.document_type.'.$model->id => 'Changed'], 'en');

        $this->assertSame('Changed', $model->name);
    }

    public function test_document_type_can_be_translated_with_lang_key()
    {
        $model = DocumentType::factory()->create(['name' => 'custom.document_type.some']);

        Lang::addLines(['custom.document_type.some' => 'Changed'], 'en');

        $this->assertSame('Changed', $model->name);
    }

    public function test_it_uses_database_name_when_no_custom_trans_available()
    {
        $model = DocumentType::factory()->create(['name' => 'Database Name']);

        $this->assertSame('Database Name', $model->name);
    }
}
