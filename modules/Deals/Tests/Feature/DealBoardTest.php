<?php
 

namespace Modules\Deals\Tests\Feature;

use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Http\Request;
use Modules\Deals\App\Board\Board;
use Modules\Deals\App\Models\Deal;
use Modules\Deals\App\Models\Pipeline;
use Tests\TestCase;

class DealBoardTest extends TestCase
{
    public function test_deals_board_summary_is_properly_calculated()
    {
        $this->signIn();

        $pipeline = Pipeline::factory()->withStages([
            ['name' => 'Stage 1'],
            ['name' => 'Stage 2'],
            ['name' => 'Stage 3'],
        ])->create();

        $stage1 = $pipeline->stages->where('name', 'Stage 1')->first();
        $stage2 = $pipeline->stages->where('name', 'Stage 2')->first();
        $stage3 = $pipeline->stages->where('name', 'Stage 3')->first();

        $pipeline->deals()->saveMany(Deal::factory()->count(5)
            ->state(new Sequence(
                ['amount' => 1100, 'stage_id' => $stage1->id],
                ['amount' => 1200, 'stage_id' => $stage1->id],
                ['amount' => 1300, 'stage_id' => $stage2->id],
                ['amount' => 1400, 'stage_id' => $stage3->id],
                ['amount' => 1500, 'stage_id' => $stage2->id],
            ))->create());

        $summary = (new Board(
            app(Request::class)
        ))->summary(
            (new Deal)->newQuery(), $pipeline->id
        );

        $this->assertEquals($summary[$stage1->id]['count'], 2);
        $this->assertEquals($summary[$stage1->id]['value'], 2300);

        $this->assertEquals($summary[$stage2->id]['count'], 2);
        $this->assertEquals($summary[$stage2->id]['value'], 2800);

        $this->assertEquals($summary[$stage3->id]['count'], 1);
        $this->assertEquals($summary[$stage3->id]['value'], 1400);
    }
}
