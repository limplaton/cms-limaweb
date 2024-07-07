<?php
 

namespace Modules\Core\Tests\Feature\Controller;

use Tests\TestCase;

class ApplicationControllerTest extends TestCase
{
    public function test_it_always_uses_the_default_app_view()
    {
        $this->signIn();

        $this->get('/')->assertViewIs('core::app');
        $this->get('/non-existent-page')->assertViewIs('core::app');
    }
}
