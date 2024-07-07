<?php
 

namespace Modules\Deals\Tests\Feature;

use Modules\Deals\App\Models\Deal;
use Tests\TestCase;

class DealStatusControllerTest extends TestCase
{
    public function test_unauthenticated_user_cannot_access_deals_status_endpoints()
    {
        $this->getJson('/api/deals/FAKE_ID/status/FAKE_STATUS')->assertUnauthorized();
    }

    public function test_an_authorized_user_can_change_the_deal_status()
    {
        $this->signIn();
        $deal = Deal::factory()->create();

        $this->putJson("/api/deals/{$deal->id}/status/won")
            ->assertOk()
            ->assertJson(['status' => 'won']);
    }

    public function test_unauthorized_user_cannot_change_the_deal_status()
    {
        $user = $this->asRegularUser()->signIn();
        $deal = Deal::factory()->for($user)->create();

        $this->putJson("/api/deals/{$deal->id}/status/won")
            ->assertForbidden();
    }

    public function test_deal_cant_be_marked_as_lost_when_has_status_won()
    {
        $this->signIn();
        $deal = Deal::factory()->won()->create();

        $this->putJson("/api/deals/{$deal->id}/status/lost")
            ->assertStatus(409);
    }

    public function test_deal_cant_be_marked_as_won_when_has_status_lost()
    {
        $this->signIn();
        $deal = Deal::factory()->lost()->create();

        $this->putJson("/api/deals/{$deal->id}/status/won")
            ->assertStatus(409);
    }
}
