<?php
 

namespace Modules\Deals\Tests\Feature;

use Modules\Deals\App\Cards\AssignedDealsBySaleAgent;
use Modules\Deals\App\Models\Deal;
use Modules\Users\App\Models\Team;
use Modules\Users\App\Models\User;
use Tests\TestCase;

class AssignedDealsBySaleAgentTest extends TestCase
{
    protected $card;

    protected function setUp(): void
    {
        $this->card = new AssignedDealsBySaleAgent;
        parent::setUp();
    }

    public function test_card_can_be_retrieved()
    {
        $userWithLessDeals = $this->signIn();

        $userWithMostDeals = User::factory()->create();
        $secondUser = User::factory()->create();

        Deal::factory()->count(2)->create([
            'amount' => 2000,
            'created_by' => $userWithMostDeals->id,
            'user_id' => $userWithMostDeals->id,
        ]);

        Deal::factory()
            ->won()
            ->create([
                'amount' => 1000,
                'created_by' => $secondUser->id,
                'user_id' => $secondUser->id,
            ]);

        $this->getJson('/api/cards/'.$this->card->uriKey())
            ->assertOk()
            ->assertJson([
                'component' => 'card-table',
                'helpText' => __('deals::deal.cards.assigned_by_sale_agent_info'),
                'name' => $this->card->name(),
            ])

            ->assertJsonPath('fields', [
                ['key' => 'name', 'label' => __('users::user.sales_agent')],
                ['key' => 'deals_count', 'label' => __('deals::deal.total_assigned')],
                ['key' => 'forecast_amount', 'label' => __('deals::deal.forecast_amount')],
                ['key' => 'closed_amount', 'label' => __('deals::deal.closed_amount')],
            ])

            ->assertJsonCount(3, 'value')
            ->assertJsonPath('value.0.name', $userWithMostDeals->name)
            ->assertJsonPath('value.0.closed_amount', '$0.00')
            ->assertJsonPath('value.0.deals_count', 2)
            ->assertJsonPath('value.0.forecast_amount', '$4,000.00')

            ->assertJsonPath('value.1.name', $secondUser->name)
            ->assertJsonPath('value.1.closed_amount', '$1,000.00')
            ->assertJsonPath('value.1.deals_count', 1)
            ->assertJsonPath('value.1.forecast_amount', '$1,000.00')

            ->assertJsonPath('value.2.name', $userWithLessDeals->name)
            ->assertJsonPath('value.2.closed_amount', '$0.00')
            ->assertJsonPath('value.2.deals_count', 0)
            ->assertJsonPath('value.2.forecast_amount', '$0.00');
    }

    public function test_card_shows_only_users_managed_by_logged_in_user()
    {
        $user = $this->asRegularUser()->withPermissionsTo('view team deals')->signIn();
        $teamUser = User::factory()->has(Team::factory()->for($user, 'manager'))->create();
        User::factory()->create();

        Deal::factory()->for($teamUser)->create([
            'user_id' => $teamUser->id,
            'created_by' => $teamUser->id,
        ]);

        $this->getJson('/api/cards/'.$this->card->uriKey())
            ->assertOk()
            ->assertJsonCount(2, 'value')
            ->assertJsonPath('value.0.name', $teamUser->name)
            ->assertJsonPath('value.1.name', $user->name);
    }
}
