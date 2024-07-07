<?php
 

namespace Modules\Activities\Tests\Feature;

use Illuminate\Testing\Fluent\AssertableJson;
use Modules\Activities\App\Cards\ActivitiesCreatedBySaleAgent;
use Modules\Core\Tests\ResourceTestCase;

class ActivitiesCreatedBySaleAgentCardTest extends ResourceTestCase
{
    protected $card;

    protected $resourceName = 'activities';

    protected function setUp(): void
    {
        parent::setUp();
        $this->card = new ActivitiesCreatedBySaleAgent;
    }

    protected function tearDown(): void
    {
        unset($this->card);
        parent::tearDown();
    }

    public function test_activities_created_by_sale_agent_card()
    {
        $this->signIn();

        $user1 = $this->createUser();
        $user2 = $this->createUser();

        $this->factory()->for($user1, 'creator')->create();
        $this->factory()->for($user2, 'creator')->count(2)->create();

        $this->getJson("api/cards/{$this->card->uriKey()}")
            ->assertJson(
                fn (AssertableJson $json) => $json->has('value', 2)
                    ->has(
                        'value.0',
                        fn ($json) => $json->where('value', $json->toArray()['label'] === $user1->name ? 1 : 2)->etc()
                    )->has(
                        'value.1',
                        fn ($json) => $json->where('value', $json->toArray()['label'] === $user1->name ? 1 : 2)->etc()
                    )->etc()
            );
    }

    public function test_unauthorized_user_cannot_see_activities_created_by_sale_agent_card()
    {
        $this->asRegularUser()->signIn();

        $this->getJson("api/cards/{$this->card->uriKey()}")->assertForbidden();
    }
}
