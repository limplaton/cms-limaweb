<?php

namespace Tests\Feature\Controller;

use Tests\TestCase;

class RequirementsControllerTest extends TestCase
{
    public function test_unauthenticated_user_cannot_access_requirements_endpoints()
    {
        $this->get('/requirements')->assertRedirect(route('login'));
        $this->post('/requirements')->assertRedirect(route('login'));
    }

    public function test_unauthorized_user_cannot_access_requirements_endpoints()
    {
        $this->asRegularUser()->signIn();

        $this->get('/requirements')->assertForbidden();
        $this->post('/requirements')->assertForbidden();
    }

    public function test_requirements_can_be_viewed()
    {
        $this->signIn();
        $this->get('/requirements')->assertSee('Required PHP Version');
    }

    public function test_requirements_can_be_confirmed()
    {
        settings()->set([
            '_app_url' => 'https://demo.crm.com',
            '_server_ip' => 'server-ip',
        ])->save();

        $this->signIn();
        $this->post('/requirements');

        $this->assertEquals(settings()->get('_app_url'), config('app.url'));
    }
}
