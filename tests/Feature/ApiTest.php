<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiTest extends TestCase
{
    public function test_non_logged_in_user_cannot_access_api()
    {
        $this->createUser();

        $this->getJson('/api/users')->assertStatus(401);
    }

    public function test_authorized_user_can_access_api()
    {
        $this->signIn();

        $this->getJson('/api/users')->assertOk();
    }
}
