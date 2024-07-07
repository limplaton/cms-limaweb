<?php
 

namespace Modules\Users\Tests\Feature;

use Tests\TestCase;

class PermissionControllerTest extends TestCase
{
    public function test_unauthenticated_user_cannot_access_permissions_endpoints()
    {
        $this->getJson('/api/permissions')->assertUnauthorized();
    }

    public function test_unauthorized_user_cannot_access_permissions_endpoints()
    {
        $this->asRegularUser()->signIn();

        $this->getJson('/api/permissions')->assertForbidden();
    }

    public function test_permissions_can_be_retrieved()
    {
        $this->signIn();

        $this->getJson('/api/permissions')->assertOk();
    }
}
