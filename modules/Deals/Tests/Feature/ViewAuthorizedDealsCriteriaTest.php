<?php
 

namespace Modules\Deals\Tests\Feature;

use Modules\Deals\App\Criteria\ViewAuthorizedDealsCriteria;
use Modules\Deals\App\Models\Deal;
use Tests\TestCase;

class ViewAuthorizedDealsCriteriaTest extends TestCase
{
    public function test_own_deals_criteria_queries_only_own_deals()
    {
        $user = $this->asRegularUser()->withPermissionsTo('view own deals')->createUser();

        Deal::factory()->for($user)->create();
        Deal::factory()->create();

        $this->signIn($user);
        $query = Deal::criteria(ViewAuthorizedDealsCriteria::class);
        $this->assertSame(1, $query->count());
    }

    public function test_it_returns_all_deals_when_user_is_authorized_to_see_all_deals()
    {
        $user = $this->asRegularUser()->withPermissionsTo('view all deals')->createUser();

        Deal::factory()->for($user)->create();
        Deal::factory()->create();

        $this->signIn($user);
        $query = Deal::criteria(ViewAuthorizedDealsCriteria::class);
        $this->assertSame(2, $query->count());

        $this->signIn();
        $query = Deal::criteria(ViewAuthorizedDealsCriteria::class);
        $this->assertSame(2, $query->count());
    }
}
