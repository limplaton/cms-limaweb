<?php
 

namespace Modules\Deals\Tests\Feature;

use Modules\Deals\App\Models\Pipeline;
use Tests\TestCase;

class UpdatePipelineDisplayOrderTest extends TestCase
{
    public function test_it_can_update_pipeline_display_order()
    {
        $this->signIn();
        $pipelines = Pipeline::factory(3)->create();

        $this->postJson('/api/pipelines/order', [
            'order' => [
                ['id' => $pipelines[2]->id, 'display_order' => 1],
                ['id' => $pipelines[1]->id, 'display_order' => 2],
                ['id' => $pipelines[0]->id, 'display_order' => 3],
            ],
        ])->assertNoContent();

        $freshPipelines = Pipeline::userOrdered()->with('userOrder')->get();

        $this->assertSame($pipelines[2]->id, $freshPipelines[0]->id);
        $this->assertSame($pipelines[1]->id, $freshPipelines[1]->id);
        $this->assertSame($pipelines[0]->id, $freshPipelines[2]->id);

        $this->assertSame(1, $freshPipelines[0]->userOrder->display_order);
        $this->assertSame(2, $freshPipelines[1]->userOrder->display_order);
        $this->assertSame(3, $freshPipelines[2]->userOrder->display_order);
    }
}
