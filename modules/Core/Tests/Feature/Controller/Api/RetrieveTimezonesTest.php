<?php
 

namespace Modules\Core\Tests\Feature\Controller\Api;

use Tests\TestCase;

class RetrieveTimezonesTest extends TestCase
{
    public function test_unauthenticated_user_cannot_access_timezones_endpoints()
    {
        $this->getJson('/api/timezones')->assertUnauthorized();
    }

    public function test_timezones_can_be_retrieved()
    {
        $this->signIn();

        $this->getJson('/api/timezones')->assertOk();
    }
}
