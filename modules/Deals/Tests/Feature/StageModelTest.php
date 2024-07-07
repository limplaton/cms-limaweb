<?php
 

namespace Modules\Deals\Tests\Feature;

use Illuminate\Support\Facades\Lang;
use Modules\Deals\App\Models\Deal;
use Modules\Deals\App\Models\Pipeline;
use Modules\Deals\App\Models\Stage;
use Tests\TestCase;

class StageModelTest extends TestCase
{
    public function test_stage_has_deals()
    {
        $stage = Stage::factory()->has(Deal::factory()->count(2))->create();

        $this->assertCount(2, $stage->deals);
    }

    public function test_stage_has_pipeline()
    {
        $stage = Stage::factory()->for(Pipeline::factory())->create();

        $this->assertInstanceOf(Pipeline::class, $stage->pipeline);
    }

    public function test_it_can_properly_retrieve_all_stages_for_option_fields()
    {
        $user = $this->signIn();

        Stage::factory()->count(5)->create();

        $options = Stage::allStagesForOptions($user);

        $this->assertCount(5, $options);
        $this->assertArrayHasKey('id', $options[0]);
        $this->assertArrayHasKey('name', $options[0]);
    }

    public function test_it_cannot_delete_stage_with_deals()
    {
        $stage = Stage::factory()->has(Deal::factory()->count(2))->create();

        $this->expectExceptionMessage(__('deals::deal.stage.delete_usage_warning'));

        $stage->delete();
    }

    public function test_stage_can_be_translated_with_custom_group()
    {
        $model = Stage::factory()->create(['name' => 'Original']);

        Lang::addLines(['custom.stage.'.$model->id => 'Changed'], 'en');

        $this->assertSame('Changed', $model->name);
    }

    public function test_stage_can_be_translated_with_lang_key()
    {
        $model = Stage::factory()->create(['name' => 'custom.stage.some']);

        Lang::addLines(['custom.stage.some' => 'Changed'], 'en');

        $this->assertSame('Changed', $model->name);
    }

    public function test_it_uses_database_name_when_no_custom_trans_available()
    {
        $model = Stage::factory()->create(['name' => 'Database Name']);

        $this->assertSame('Database Name', $model->name);
    }
}
