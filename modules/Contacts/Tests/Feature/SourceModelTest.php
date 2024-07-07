<?php
 

namespace Modules\Contacts\Tests\Feature;

use Illuminate\Support\Facades\Lang;
use Modules\Contacts\App\Models\Company;
use Modules\Contacts\App\Models\Contact;
use Modules\Contacts\App\Models\Source;
use Tests\TestCase;

class SourceModelTest extends TestCase
{
    public function test_source_can_be_primary()
    {
        $source = Source::factory()->primary()->create();
        $this->assertTrue($source->isPrimary());

        $source->flag = null;
        $source->save();

        $this->assertFalse($source->isPrimary());
    }

    public function test_source_has_contacts()
    {
        $source = Source::factory()->has(Contact::factory()->count(2))->create();

        $this->assertCount(2, $source->contacts);
    }

    public function test_source_has_companies()
    {
        $source = Source::factory()->has(Company::factory()->count(2))->create();

        $this->assertCount(2, $source->companies);
    }

    public function test_source_has_by_flag_scope()
    {
        $source = Source::factory()->create(['flag' => 'custom-flag']);

        $byFlag = Source::findByFlag('custom-flag');

        $this->assertInstanceOf(Source::class, $byFlag);
        $this->assertEquals($source->id, $byFlag->id);
    }

    public function test_primary_source_cannot_be_deleted()
    {
        $source = Source::factory()->primary()->create();
        $this->expectExceptionMessage(__('contacts::source.delete_primary_warning'));

        $source->delete();
    }

    public function test_source_with_contacts_cannot_be_deleted()
    {
        $source = Source::factory()->has(Contact::factory()->for($this->createUser()))->create();

        $this->expectExceptionMessage(__(
            'core::resource.associated_delete_warning',
            [
                'resource' => __('contacts::source.source'),
            ]
        ));

        $source->delete();
    }

    public function test_source_with_companies_cannot_be_deleted()
    {
        $source = Source::factory()->has(Company::factory())->create();

        $this->expectExceptionMessage(__(
            'core::resource.associated_delete_warning',
            [
                'resource' => __('contacts::source.source'),
            ]
        ));

        $source->delete();
    }

    public function test_source_can_be_translated_with_custom_group()
    {
        $model = Source::factory()->create(['name' => 'Original']);

        Lang::addLines(['custom.source.'.$model->id => 'Changed'], 'en');

        $this->assertSame('Changed', $model->name);
    }

    public function test_source_can_be_translated_with_lang_key()
    {
        $model = Source::factory()->create(['name' => 'custom.source.some']);

        Lang::addLines(['custom.source.some' => 'Changed'], 'en');

        $this->assertSame('Changed', $model->name);
    }

    public function test_it_uses_database_name_when_no_custom_trans_available()
    {
        $model = Source::factory()->create(['name' => 'Database Name']);

        $this->assertSame('Database Name', $model->name);
    }
}
