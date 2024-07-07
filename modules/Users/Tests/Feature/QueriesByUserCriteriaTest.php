<?php
 

namespace Modules\Users\Tests\Feature;

use Modules\Contacts\App\Models\Contact;
use Modules\Users\App\Criteria\QueriesByUserCriteria;
use Tests\TestCase;

class QueriesByUserCriteriaTest extends TestCase
{
    public function test_it_uses_by_default_the_current_logged_in_user()
    {
        $user = $this->signIn($this->createUser());

        Contact::factory()->count(2)->create();
        Contact::factory()->for($user)->create();

        $query = (new Contact())->criteria(QueriesByUserCriteria::class);

        $this->assertSame(1, $query->count());
    }

    public function test_it_uses_the_provided_user()
    {
        $user = $this->signIn($this->createUser());
        $user2 = $this->createUser();

        Contact::factory()->for($user)->count(2)->create();
        Contact::factory()->for($user2)->create();

        $query = (new Contact())->criteria(new QueriesByUserCriteria($user2));

        $this->assertSame(1, $query->count());

        $query = (new Contact())->criteria(new QueriesByUserCriteria($user2->id));

        $this->assertSame(1, $query->count());
    }

    public function test_it_accepts_custom_column_name()
    {
        $user = $this->signIn($this->createUser());
        $user2 = $this->createUser();

        Contact::factory()->count(2)->for($user2, 'creator')->create();
        Contact::factory()->for($user, 'creator')->create();

        $query = (new Contact())->criteria(new QueriesByUserCriteria($user, 'created_by'));

        $this->assertSame(1, $query->count());
    }
}
