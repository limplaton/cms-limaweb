<?php
 

namespace Modules\Deals\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Testing\Fluent\AssertableJson;
use Modules\Deals\App\Board\Board;
use Modules\Deals\App\Events\DealMovedToStage;
use Modules\Deals\App\Models\Deal;
use Modules\Deals\App\Models\Pipeline;
use Modules\Deals\App\Models\Stage;
use Tests\TestCase;

class DealBoardControllerTest extends TestCase
{
    public function test_unauthenticated_user_cannot_access_deals_board_endpoints()
    {
        $pipeline = Pipeline::factory()->create();

        $this->getJson('/api/deals/board')->assertUnauthorized();
        $this->getJson('/api/deals/board/'.$pipeline->id.'/summary')->assertUnauthorized();
        $this->postJson('/api/deals/board/'.$pipeline->id)->assertUnauthorized();
        $this->getJson('/api/deals/board/'.$pipeline->id.'/FAKE_STAGE_ID')->assertUnauthorized();
    }

    public function test_deals_can_be_updated_via_board()
    {
        $user = $this->signIn();

        $pipeline = Pipeline::factory()->withStages()->create();
        $deals = Deal::factory(3)->for($user)->for($pipeline)->create();
        $newStage = Stage::factory()->for($pipeline)->create();
        $boardOrder = [1000, 2000, 3000];

        Event::fake();

        $payload = $deals->map(function ($deal, $index) use ($newStage, $boardOrder) {
            return [
                'id' => $deal->id,
                'board_order' => $boardOrder[$index],
                'stage_id' => $index === 2 ? $deal->stage_id : $newStage->id,
                'swatch_color' => null,
            ];
        })->all();

        $this->postJson('/api/deals/board/'.$pipeline->id, $payload)->assertOk();

        $deals = $deals->map->fresh();

        $this->assertEquals($deals->get(0)->board_order, $boardOrder[0]);
        $this->assertEquals($deals->get(1)->board_order, $boardOrder[1]);
        $this->assertEquals($deals->get(2)->board_order, $boardOrder[2]);

        $this->assertEquals($deals->get(0)->stage_id, $newStage->id);
        $this->assertEquals($deals->get(1)->stage_id, $newStage->id);
        $this->assertEquals($deals->get(2)->stage_id, $deals->get(2)->stage_id);

        Event::assertDispatchedTimes(DealMovedToStage::class, 2);
    }

    public function test_user_cannot_update_deals_via_board_that_is_not_authorized_to_update()
    {
        $signedInUser = $this->asRegularUser()
            ->withPermissionsTo('edit own deals')
            ->signIn();

        $user = $this->createUser();
        $pipeline = Pipeline::factory()->withStages()->create();
        $otherDeal = Deal::factory()->for($pipeline)->for($user)->create(['board_order' => 500]);
        $dealForSignedIn = Deal::factory()->for($pipeline)->for($signedInUser)->create(['board_order' => 501]);
        $newStage = Stage::factory()->for($pipeline)->create();

        Event::fake();

        $this->postJson('/api/deals/board/'.$pipeline->id, [
            [
                'id' => $otherDeal->id,
                'board_order' => 1000,
                'stage_id' => $newStage->id,
                'swatch_color' => '#ffffff',
            ],
            [
                'id' => $dealForSignedIn->id,
                'board_order' => 1500,
                'stage_id' => $newStage->id,
                'swatch_color' => '#333333',
            ],
        ]);

        $this->assertEquals($otherDeal->fresh()->board_order, 500);
        $this->assertEquals($otherDeal->stage_id, $otherDeal->fresh()->stage_id);

        $this->assertEquals($dealForSignedIn->fresh()->board_order, 1500);
        $this->assertEquals($newStage->id, $dealForSignedIn->fresh()->stage_id);
        $this->assertEquals('#333333', $dealForSignedIn->fresh()->swatch_color);

        Event::assertDispatchedTimes(DealMovedToStage::class, 1);
    }

    public function test_stage_moved_activity_is_logged_when_deal_stage_is_updated_via_board()
    {
        $this->signIn();

        $pipeline = Pipeline::factory()->withStages()->create();

        $deal = Deal::factory()->create();
        $newStage = Stage::factory()->for($pipeline)->create();

        $this->postJson('/api/deals/board/'.$pipeline->id, [
            [
                'id' => $deal->id,
                'stage_id' => $newStage->id,
                'board_order' => 1,
                'swatch_color' => null,
            ],
        ]);

        $latestActivity = $deal->changeLog()->orderBy('id', 'desc')->first();

        $this->assertStringContainsString('deals::deal.timeline.stage.moved', (string) $latestActivity->properties);
    }

    public function test_triggers_model_events_when_updating_deals_via_board()
    {
        $this->signIn();

        $pipeline = Pipeline::factory()->withStages()->create();
        $deals = Deal::factory(2)->create();

        Event::fake();

        $this->postJson('/api/deals/board/'.$pipeline->id, [
            [
                'id' => $deals->all()[0]->id,
                'stage_id' => $pipeline->stages[0]->id,
                'board_order' => 1,
                'swatch_color' => null,
            ],
            [
                'id' => $deals->all()[1]->id,
                'stage_id' => $pipeline->stages[2]->id,
                'board_order' => 2,
                'swatch_color' => null,
            ],
        ]);

        // Assert an event was dispatched twice...
        Event::assertDispatched('eloquent.updating: '.Deal::class, 2);
        Event::assertDispatched('eloquent.updated: '.Deal::class, 2);
    }

    public function test_updated_at_column_is_updated_only_when_deal_stage_changes()
    {
        $this->signIn();

        $pipeline = Pipeline::factory()->withStages()->create();
        $deals = Deal::factory(2)->create([
            'updated_at' => now()->subDays(2),
        ]);

        $updatedAt = $deals[0]->updated_at->format('Y-m-d H:i:s');

        $this->postJson('/api/deals/board/'.$pipeline->id, [
            [
                'id' => $deals[0]->id,
                'stage_id' => $pipeline->stages->filter(
                    fn ($stage) => $stage->id !== $deals[0]->stage_id
                )[0]->id,
                'board_order' => 2,
                'swatch_color' => null,
            ],
            [
                'id' => $deals[1]->id,
                'stage_id' => $deals[1]->stage_id,
                'board_order' => 3,
                'swatch_color' => null,
            ],
        ])->assertOk();

        $this->assertNotSame(
            $updatedAt,
            $deals[0]->fresh()->updated_at->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            $deals[1]->updated_at->format('Y-m-d H:i:s'),
            $deals[1]->fresh()->updated_at->format('Y-m-d H:i:s')
        );
    }

    /**
     * The deal name is needed for the task create to add the name into the select
     */
    public function test_deal_name_is_returned_in_board_json_resource()
    {
        $this->signIn();
        $pipeline = Pipeline::factory()->withStages()->create();
        Deal::factory()->for($pipeline->stages->first())->create(['name' => 'Deal Name']);

        $this->getJson('/api/deals/board/'.$pipeline->id)
            ->assertJson(function (AssertableJson $json) {
                $json->has('0.cards.0', function ($json) {
                    $json->has('name')->where('name', 'Deal Name')->etc();
                })->etc();
            });
    }

    public function test_all_pipeline_stages_are_returned_on_deals_board()
    {
        $this->signIn();

        $pipeline = Pipeline::factory()->withStages()->create();
        Deal::factory()->for($pipeline)->create();

        $this->getJson('/api/deals/board/'.$pipeline->id)->assertJsonCount(
            $pipeline->stages->count()
        );
    }

    public function test_deal_board_filters_are_applied()
    {
        $this->signIn();

        $pipeline = Pipeline::factory()->withStages()->create();
        $deals = Deal::factory(5)->for($pipeline)->create();

        $response = $this->getJson('/api/deals/board/'.$pipeline->id.'?'.http_build_query([
            'rules' => [
                'condition' => 'and',
                'children' => [
                    [
                        'type' => 'rule',
                        'query' => [
                            'type' => 'text',
                            'rule' => 'name',
                            'operator' => 'equal',
                            'operand' => '',
                            'value' => $deals->first()->name,
                        ],
                    ],
                ],
            ],
        ]));

        foreach ($response->getData() as $stage) {
            if ($stage->id === $deals->first()->stage_id) {
                $this->assertCount(1, $stage->cards);
            } else {
                $this->assertCount(0, $stage->cards);
            }
        }
    }

    public function test_board_stages_are_in_correct_order()
    {
        $this->signIn();

        $pipeline = Pipeline::factory()->withStages()->create();

        $last = $pipeline->stages->first();
        $last->display_order = 1000;
        $last->save();

        $first = $pipeline->stages->last();
        $first->display_order = 1;
        $first->save();

        $response = $this->getJson('/api/deals/board/'.$pipeline->id);

        $lastIndex = $pipeline->stages->count() - 1;
        $this->assertEquals($response->getData()[0]->id, $first->id);
        $this->assertEquals($response->getData()[$lastIndex]->id, $last->id);
    }

    public function test_deals_board_summary_can_be_retrieved()
    {
        $this->signIn();

        $pipeline = Pipeline::factory()->withStages()->create();
        Deal::factory(5)->for($pipeline)->create();

        $response = $this->getJson('/api/deals/board/'.$pipeline->id.'/summary')
            ->assertJsonCount($pipeline->stages->count());

        foreach ($response->getData() as $stageId => $summary) {
            $this->assertArrayHasKey('value', (array) $summary);
            $this->assertArrayHasKey('count', (array) $summary);
        }
    }

    public function test_it_loads_more_deals()
    {
        $this->signIn();

        Board::$perPage = 5;
        $pipeline = Pipeline::factory()->has(Stage::factory())->create();

        Deal::factory(10)->for($pipeline)->for($pipeline->stages[0])->create();
        $stage = $pipeline->stages[0];

        $this->getJson("/api/deals/board/$pipeline->id/$stage->id?page=2")->assertJsonCount(5, 'cards');
    }

    public function test_it_can_fully_refresh_board_stage()
    {
        $this->signIn();

        Board::$perPage = 5;

        $pipeline = Pipeline::factory()->has(Stage::factory())->create();
        Deal::factory(11)->for($pipeline)->for($pipeline->stages[0])->create();
        $stage = $pipeline->stages[0];

        $this->getJson("/api/deals/board/$pipeline->id?pages[$stage->id]=2")->assertJsonCount(10, '0.cards');
    }
}
