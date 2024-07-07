<?php
 

namespace Modules\Deals\Tests\Feature;

use Illuminate\Support\Facades\Lang;
use Modules\Deals\App\Models\Deal;
use Modules\Deals\App\Models\Pipeline;
use Tests\TestCase;

class PipelineModelTest extends TestCase
{
    public function test_pipeline_can_be_primary()
    {
        $pipeline = Pipeline::factory()->primary()->create();

        $this->assertTrue($pipeline->isPrimary());
    }

    public function test_pipeline_has_deals()
    {
        $pipeline = Pipeline::factory()->withStages()->has(Deal::factory()->count(2))->create();

        $this->assertCount(2, $pipeline->deals);
    }

    public function test_it_cannot_delete_primary_pipeline()
    {
        $pipeline = Pipeline::factory()->primary()->create();

        $this->expectExceptionMessage(__('deals::deal.pipeline.delete_primary_warning'));

        $pipeline->delete();
    }

    public function test_it_cannot_delete_pipeline_with_deals()
    {
        $pipeline = Pipeline::factory()->withStages()->has(Deal::factory()->count(2))->create();

        $this->expectExceptionMessage(__('deals::deal.pipeline.delete_usage_warning_deals'));

        $pipeline->delete();
    }

    public function test_pipeline_can_be_translated_with_custom_group()
    {
        $model = Pipeline::factory()->create(['name' => 'Original']);

        Lang::addLines(['custom.pipeline.'.$model->id => 'Changed'], 'en');

        $this->assertSame('Changed', $model->name);
    }

    public function test_pipeline_can_be_translated_with_lang_key()
    {
        $model = Pipeline::factory()->create(['name' => 'custom.pipeline.some']);

        Lang::addLines(['custom.pipeline.some' => 'Changed'], 'en');

        $this->assertSame('Changed', $model->name);
    }

    public function test_it_uses_database_name_when_no_custom_trans_available()
    {
        $model = Pipeline::factory()->create(['name' => 'Database Name']);

        $this->assertSame('Database Name', $model->name);
    }
}
