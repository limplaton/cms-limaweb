<?php
 

namespace Modules\Calls\Tests\Feature;

use Illuminate\Support\Facades\Lang;
use Modules\Calls\App\Models\Call;
use Modules\Calls\App\Models\CallOutcome;
use Tests\TestCase;

class CallOutcomeModelTest extends TestCase
{
    public function test_outcome_has_calls()
    {
        $outcome = CallOutcome::factory()->has(Call::factory()->count(2))->create();

        $this->assertCount(2, $outcome->calls);
    }

    public function test_call_outcome_can_be_translated_with_custom_group()
    {
        $model = CallOutcome::factory()->create(['name' => 'Original']);

        Lang::addLines(['custom.call_outcome.'.$model->id => 'Changed'], 'en');

        $this->assertSame('Changed', $model->name);
    }

    public function test_call_outcome_can_be_translated_with_lang_key()
    {
        $model = CallOutcome::factory()->create(['name' => 'custom.call_outcome.some']);

        Lang::addLines(['custom.call_outcome.some' => 'Changed'], 'en');

        $this->assertSame('Changed', $model->name);
    }

    public function test_it_uses_database_name_when_no_custom_trans_available()
    {
        $model = CallOutcome::factory()->create(['name' => 'Database Name']);

        $this->assertSame('Database Name', $model->name);
    }
}
