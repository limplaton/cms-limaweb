<?php
 

namespace Modules\Core\Tests\Feature\Controller\Api;

use Modules\Core\Database\Seeders\CountriesSeeder;
use Tests\TestCase;

class RetrieveCountriesTest extends TestCase
{
    public function test_unauthenticated_cannot_access_country_endpoints()
    {
        $this->getJson('/api/countries')->assertUnauthorized();
    }

    public function test_user_can_fetch_countries()
    {
        $this->signIn();

        $this->seed(CountriesSeeder::class);

        $this->getJson('/api/countries')->assertOk();
    }
}
