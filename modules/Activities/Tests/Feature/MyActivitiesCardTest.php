<?php
 

namespace Modules\Activities\Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Testing\Fluent\AssertableJson;
use Modules\Activities\App\Cards\MyActivities;
use Modules\Core\Tests\ResourceTestCase;

class MyActivitiesCardTest extends ResourceTestCase
{
    protected $card;

    protected $resourceName = 'activities';

    protected function setUp(): void
    {
        parent::setUp();
        $this->card = new MyActivities;
    }

    protected function tearDown(): void
    {
        unset($this->card);
        parent::tearDown();
    }

    public function test_my_activities_card()
    {
        $user = $this->signIn();

        $activity = $this->factory()->inProgress()->for($user)->create();

        $this->getJson("api/cards/{$this->card->uriKey()}")
            ->assertJsonCount(1, 'value.data')
            ->assertJson(function (AssertableJson $json) use ($activity) {
                $json->has('value.data.0', function ($json) use ($activity) {
                    $json->where('id', $activity->id)
                        ->where('title', $activity->title)
                        ->where('is_completed', false)
                        ->where('path', $activity->path())
                        ->where('activity_type_id', $activity->activity_type_id)
                        ->where('due_date', Carbon::parse($activity->full_due_date)->toJSON())
                        ->where('type.name', $activity->type->name)
                        ->has('authorizations')
                        ->etc();
                })->etc();
            });
    }

    public function test_my_activities_card_shows_activities_for_logged_in_user_only()
    {
        $this->signIn();
        $user = $this->createUser();
        $this->factory()->inProgress()->for($user)->create();

        $this->getJson("api/cards/{$this->card->uriKey()}")
            ->assertJsonCount(0, 'value.data');
    }

    public function test_it_does_not_query_the_completed_activities_on_my_activities_card()
    {
        $user = $this->signIn();

        $this->factory()->for($user)->completed()->create();
        $this->factory()->inProgress()->for($user)->create();

        $this->getJson("api/cards/{$this->card->uriKey()}")
            ->assertJsonCount(1, 'value.data');
    }
}
