<?php
 

namespace Modules\Contacts\Tests\Feature;

use Illuminate\Support\Facades\Lang;
use Modules\Contacts\App\Models\Company;
use Modules\Contacts\App\Models\Industry;
use Tests\TestCase;

class IndustryModelTest extends TestCase
{
    public function test_industry_has_companies()
    {
        $industry = Industry::factory()->has(Company::factory()->count(2))->create();

        $this->assertCount(2, $industry->companies);
    }

    public function test_industry_with_companies_cannot_be_deleted()
    {
        $industry = Industry::factory()->has(Company::factory())->create();

        $this->expectExceptionMessage(__(
            'core::resource.associated_delete_warning',
            [
                'resource' => __('contacts::company.industry.industry'),
            ]
        ));

        $industry->delete();
    }

    public function test_industry_can_be_translated_with_custom_group()
    {
        $model = Industry::factory()->create(['name' => 'Original']);

        Lang::addLines(['custom.industry.'.$model->id => 'Changed'], 'en');

        $this->assertSame('Changed', $model->name);
    }

    public function test_industry_can_be_translated_with_lang_key()
    {
        $model = Industry::factory()->create(['name' => 'custom.industry.some']);

        Lang::addLines(['custom.industry.some' => 'Changed'], 'en');

        $this->assertSame('Changed', $model->name);
    }

    public function test_it_uses_database_name_when_no_custom_trans_available()
    {
        $model = Industry::factory()->create(['name' => 'Database Name']);

        $this->assertSame('Database Name', $model->name);
    }
}
