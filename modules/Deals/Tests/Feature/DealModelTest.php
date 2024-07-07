<?php
 

namespace Modules\Deals\Tests\Feature;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Modules\Activities\App\Models\Activity;
use Modules\Calls\App\Models\Call;
use Modules\Contacts\App\Models\Company;
use Modules\Contacts\App\Models\Contact;
use Modules\Deals\App\Enums\DealStatus;
use Modules\Deals\App\Models\Deal;
use Modules\Deals\App\Models\Pipeline;
use Modules\Deals\App\Models\Stage;
use Modules\Notes\App\Models\Note;
use Modules\Users\App\Models\User;
use Tests\TestCase;

class DealModelTest extends TestCase
{
    public function test_when_deal_created_by_not_provided_uses_current_user_id()
    {
        $user = $this->signIn();

        $deal = Deal::factory(['created_by' => null])->create();

        $this->assertEquals($deal->created_by, $user->id);
    }

    public function test_deal_created_by_can_be_provided()
    {
        $user = $this->createUser();

        $deal = Deal::factory()->for($user, 'creator')->create();

        $this->assertEquals($deal->created_by, $user->id);
    }

    public function test_deal_has_path()
    {
        $deal = Deal::factory()->create();

        $this->assertEquals('/deals/'.$deal->id, $deal->path());
    }

    public function test_deal_has_user()
    {
        $deal = Deal::factory()->for(User::factory())->create();

        $this->assertInstanceOf(User::class, $deal->user);
    }

    public function test_deal_has_display_name_attribute()
    {
        $deal = Deal::factory(['name' => 'Deal name'])->make();

        $this->assertEquals('Deal name', $deal->displayName());
    }

    public function test_deal_has_companies()
    {
        $deal = Deal::factory()->has(Company::factory()->count(2))->create();

        $this->assertCount(2, $deal->companies);
    }

    public function test_deal_has_contacts()
    {
        $deal = Deal::factory()->has(Contact::factory()->count(2))->create();

        $this->assertCount(2, $deal->contacts);
    }

    public function test_deal_has_calls()
    {
        $deal = Deal::factory()->has(Call::factory()->count(2))->create();

        $this->assertCount(2, $deal->calls);
    }

    public function test_deal_has_notes()
    {
        $deal = Deal::factory()->has(Note::factory()->count(2))->create();

        $this->assertCount(2, $deal->notes);
    }

    public function test_deal_has_activities()
    {
        $deal = Deal::factory()->has(Activity::factory()->count(2))->create();

        $this->assertCount(2, $deal->activities);
    }

    public function test_deal_has_pipeline()
    {
        $deal = Deal::factory()->for(Pipeline::factory()->withStages())->create();

        $this->assertInstanceOf(Pipeline::class, $deal->pipeline);
    }

    public function test_deal_has_stage()
    {
        $deal = Deal::factory()->for(Stage::factory())->create();

        $this->assertInstanceOf(Stage::class, $deal->stage);
    }

    public function test_deal_has_stages_history()
    {
        $deal = new Deal;

        $this->assertInstanceOf(BelongsToMany::class, $deal->stagesHistory());
    }

    public function test_it_can_record_stage_history()
    {
        $deal = Deal::factory()->for(Stage::factory())->create();

        $deal->recordStageHistory($deal->stage_id);

        // With the one when the deal is created
        $this->assertCount(2, $deal->stagesHistory);
        $this->assertNotNull($deal->lastStageHistory()['history']->entered_at);
        $this->assertEquals($deal->lastStageHistory()->id, $deal->stage_id);
    }

    public function test_it_can_start_deal_stage_history()
    {
        $deal = Deal::factory()->for(Stage::factory())->create();

        $deal->startStageHistory();

        // With the one when the deal is created
        $this->assertCount(2, $deal->stagesHistory);
        $this->assertNotNull($deal->lastStageHistory()['history']->entered_at);
        $this->assertEquals($deal->lastStageHistory()->id, $deal->stage_id);
    }

    public function test_deal_last_stage_history_can_be_retrieved()
    {
        $deal = Deal::factory()->for(Stage::factory())->open()->create();

        $this->assertInstanceOf(Stage::class, $deal->lastStageHistory());
    }

    public function test_deal_stage_history_is_started_when_open_deal_is_created()
    {
        $deal = Deal::factory()->for(Stage::factory())->open()->create();

        $this->assertNotEmpty($deal->stagesHistory);
        $this->assertCount(1, $deal->stagesHistory);
        $this->assertNotNull($deal->stagesHistory[0]['history']->entered_at);
        $this->assertNull($deal->stagesHistory[0]['history']->left_at);
    }

    public function test_deal_stage_history_is_not_started_when_won_deal_is_created()
    {
        $deal = Deal::factory()->for(Stage::factory())->won()->create();

        $this->assertEmpty($deal->stagesHistory);
        $this->assertCount(0, $deal->stagesHistory);
    }

    public function test_deal_stage_history_is_not_started_when_lost_deal_is_created()
    {
        $deal = Deal::factory()->for(Stage::factory())->lost()->create();

        $this->assertEmpty($deal->stagesHistory);
        $this->assertCount(0, $deal->stagesHistory);
    }

    public function test_deal_stage_history_is_stopped_when_status_is_changed_to_won()
    {
        $deal = Deal::factory()->for(Stage::factory())->open()->create();
        $deal->markAsWon();

        $this->assertNotNull($deal->lastStageHistory()['history']->left_at);
    }

    public function test_deal_stage_history_is_stopped_when_status_is_changed_to_lost()
    {
        $deal = Deal::factory()->for(Stage::factory())->open()->create();
        $deal->markAsLost();

        $this->assertNotNull($deal->lastStageHistory()['history']->left_at);
    }

    public function test_deal_last_stage_history_timing_can_be_stopped()
    {
        $deal = Deal::factory()->for(Stage::factory())->open()->create();

        $deal->stopLastStageTiming();

        $this->assertNotNull($deal->stagesHistory[0]['history']->left_at);
    }

    public function test_deal_history_stages_are_always_sorted_by_newest()
    {
        $enteredAt = '2021-11-21 12:00:00';
        Carbon::setTestNow($enteredAt);
        $deal = Deal::factory()->for(Stage::factory())->create();
        Carbon::setTestNow(null);
        $deal->stopLastStageTiming();
        $enteredAt = '2021-11-21 12:05:00';
        Carbon::setTestNow($enteredAt);
        $deal->startStageHistory();
        $this->assertEquals($enteredAt, $deal->stagesHistory[0]['history']->entered_at);
    }

    public function test_deal_time_in_stages_is_properly_calculated()
    {
        $stages = Stage::factory()->count(2)->create();

        $enteredAtForStage1 = '2021-11-21 12:00:00';
        Carbon::setTestNow($enteredAtForStage1);
        $deal = Deal::factory()->for($stages[0])->create();
        $leftAtForStage1 = '2021-11-21 12:05:00';

        Carbon::setTestNow($leftAtForStage1);
        $deal->stopLastStageTiming(); // total time 5 minutes, 300 in seconds

        $deal->stage_id = $stages[1]->id;
        $enteredAtForStage2 = '2021-11-21 12:06:00';
        Carbon::setTestNow($enteredAtForStage2);
        $deal->save();

        $leftAtForStage2 = '2021-11-21 12:10:00';
        Carbon::setTestNow($leftAtForStage2);
        $deal->stopLastStageTiming(); // total time 4 minutes, 240 in seconds

        $timeInStages = $deal->timeInStages();

        $this->assertEquals(300, $timeInStages[$stages[0]->id]);
        $this->assertEquals(240, $timeInStages[$stages[1]->id]);
    }

    public function test_deal_stage_changed_date_is_updated_when_stage_is_changed()
    {
        $stages = Stage::factory()->count(2)->create();
        $deal = Deal::factory()->for($stages[0])->create();

        $deal->stage_id = $stages[1]->id;
        $deal->save();

        $this->assertNotNull($deal->stage_changed_date);
    }

    public function test_stage_history_is_started_when_stage_is_changed_and_deal_is_with_status_open()
    {
        $stages = Stage::factory()->count(2)->create();
        $deal = Deal::factory()->for($stages[0])->open()->create();

        $deal->stage_id = $stages[1]->id;
        $deal->save();

        // +1 from stage history when created
        $this->assertCount(2, $deal->stagesHistory);
    }

    public function test_stage_history_is_not_started_when_stage_is_changed_and_deal_is_with_status_won()
    {
        $stages = Stage::factory()->count(2)->create();
        $deal = Deal::factory()->for($stages[0])->won()->create();

        $deal->stage_id = $stages[1]->id;
        $deal->save();

        $this->assertCount(0, $deal->stagesHistory);
    }

    public function test_stage_history_is_not_started_when_stage_is_changed_and_deal_is_with_status_lost()
    {
        $stages = Stage::factory()->count(2)->create();
        $deal = Deal::factory()->for($stages[0])->lost()->create();

        $deal->stage_id = $stages[1]->id;
        $deal->save();

        $this->assertCount(0, $deal->stagesHistory);
    }

    public function test_it_does_not_stop_latest_stage_history_if_its_already_stopped()
    {
        $deal = Deal::factory()->for(Stage::factory())->create();

        $deal->stopLastStageTiming();
        $lastStoppedTime = $deal->stagesHistory[0]['history']->left_at;
        $deal->stopLastStageTiming();

        $this->assertEquals($lastStoppedTime, $deal->stagesHistory()->first()['history']->left_at);
    }

    public function test_deal_status_attributes_are_properly_updated()
    {
        // Create
        $deal1 = Deal::factory()->open()->create();

        $this->assertNull($deal1->won_date);
        $this->assertNull($deal1->lost_date);
        $this->assertNull($deal1->lost_reason);

        $deal2 = Deal::factory()->won()->create(['lost_reason' => 'Reason', 'lost_date' => now()]);

        $this->assertNotNull($deal2->won_date);
        $this->assertNull($deal2->lost_date);
        $this->assertNull($deal2->lost_reason);

        $deal3 = Deal::factory()->lost()->create(['won_date' => now()]);

        $this->assertNotNull($deal3->lost_date);
        $this->assertNull($deal3->won_date);
        $this->assertNull($deal3->lost_reason);

        // Update
        $deal3->markAsWon();

        $this->assertNotNull($deal3->won_date);
        $this->assertNull($deal3->lost_date);
        $this->assertNull($deal3->lost_reason);

        $deal2->markAsLost('Updated Lost Reason');

        $this->assertNull($deal2->won_date);
        $this->assertNotNull($deal2->lost_date);
        $this->assertEquals('Updated Lost Reason', $deal2->lost_reason);
    }

    public function test_it_does_not_allow_changing_status_attributes_manually_when_updating()
    {
        $deal = Deal::factory()->open()->create();
        $deal->won_date = '2021-11-21 12:00:00';
        $deal->lost_date = '2021-11-21 12:00:00';
        $deal->lost_reason = 'Lost Reason';
        $deal->save();

        $this->assertNull($deal->won_date);
        $this->assertNull($deal->lost_date);
        $this->assertNull($deal->lost_reason);
    }

    public function test_it_does_allow_changing_the_lost_reason_when_deal_is_with_status_lost()
    {
        $deal = Deal::factory()->lost()->create();
        $deal->lost_reason = 'Changed Reason';
        $deal->save();
        $this->assertEquals('Changed Reason', $deal->lost_reason);
    }

    public function test_stages_history_is_started_when_deal_is_marked_as_open()
    {
        $deal = Deal::factory()->lost()->create();

        $deal->markAsOpen();

        $this->assertCount(1, $deal->stagesHistory);
    }

    public function test_deal_has_badges_for_status()
    {
        $variants = DealStatus::badgeVariants();

        $this->assertCount(3, $variants);
        $this->assertEquals('neutral', $variants['open']);
        $this->assertEquals('success', $variants['won']);
        $this->assertEquals('danger', $variants['lost']);
    }

    public function test_deal_has_total_column()
    {
        $this->assertEquals('amount', (new Deal)->totalColumn());
    }

    public function test_it_queries_closed_deals()
    {
        Deal::factory()->won()->create();
        Deal::factory()->lost()->create();
        Deal::factory()->open()->create();

        $this->assertSame(2, Deal::closed()->count());
    }

    public function test_it_queries_won_deals()
    {
        Deal::factory()->won()->create();
        Deal::factory()->lost()->create();
        Deal::factory()->open()->create();

        $this->assertSame(1, Deal::won()->count());
    }

    public function test_it_queries_open_deals()
    {
        Deal::factory()->won()->create();
        Deal::factory()->lost()->create();
        Deal::factory()->open()->create();

        $this->assertSame(1, Deal::open()->count());
    }

    public function test_it_queries_lost_deals()
    {
        Deal::factory()->won()->create();
        Deal::factory()->lost()->create();
        Deal::factory()->open()->create();

        $this->assertSame(1, Deal::lost()->count());
    }
}
