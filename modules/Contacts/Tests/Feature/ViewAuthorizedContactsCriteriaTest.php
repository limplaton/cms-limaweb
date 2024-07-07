<?php
 

namespace Modules\Contacts\Tests\Feature;

use Modules\Contacts\App\Criteria\ViewAuthorizedContactsCriteria;
use Modules\Contacts\App\Models\Contact;
use Tests\TestCase;

class ViewAuthorizedContactsCriteriaTest extends TestCase
{
    public function test_own_contacts_criteria_queries_only_own_contacts()
    {
        $user = $this->asRegularUser()->withPermissionsTo('view own contacts')->createUser();

        Contact::factory()->for($user)->create();
        Contact::factory()->create();

        $this->signIn($user);

        $query = Contact::criteria(ViewAuthorizedContactsCriteria::class);

        $this->assertSame(1, $query->count());
    }

    public function test_it_returns_all_contacts_when_user_is_authorized_to_see_all_contacts()
    {
        $user = $this->asRegularUser()->withPermissionsTo('view all contacts')->createUser();

        Contact::factory()->for($user)->create();
        Contact::factory()->create();

        $this->signIn($user);

        $query = Contact::criteria(ViewAuthorizedContactsCriteria::class);

        $this->assertSame(2, $query->count());

        $this->signIn();
        $this->assertSame(2, $query->count());
    }
}
