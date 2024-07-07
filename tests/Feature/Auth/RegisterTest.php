<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class RegisterTest extends TestCase
{
    public function test_user_is_not_allowed_to_register()
    {
        $this->get('/register')->assertStatus(302);
        $this->post('/register')->assertStatus(405);
    }
}
