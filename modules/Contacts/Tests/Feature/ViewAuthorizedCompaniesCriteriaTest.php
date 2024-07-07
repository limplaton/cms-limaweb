<?php
 

namespace Modules\Contacts\Tests\Feature;

use Modules\Contacts\App\Criteria\ViewAuthorizedCompaniesCriteria;
use Modules\Contacts\App\Models\Company;
use Tests\TestCase;

class ViewAuthorizedCompaniesCriteriaTest extends TestCase
{
    public function test_own_companies_criteria_queries_only_own_companies()
    {
        $user = $this->asRegularUser()->withPermissionsTo('view own companies')->createUser();

        Company::factory()->for($user)->create();
        Company::factory()->create();

        $this->signIn($user);

        $query = Company::criteria(ViewAuthorizedCompaniesCriteria::class);

        $this->assertSame(1, $query->count());
    }

    public function test_it_returns_all_companies_when_user_is_authorized_to_see_all_companies()
    {
        $user = $this->asRegularUser()->withPermissionsTo('view all companies')->createUser();

        Company::factory()->for($user)->create();
        Company::factory()->create();

        $this->signIn($user);

        $query = Company::criteria(ViewAuthorizedCompaniesCriteria::class);

        $this->assertSame(2, $query->count());

        $this->signIn();
        $this->assertSame(2, $query->count());
    }
}
