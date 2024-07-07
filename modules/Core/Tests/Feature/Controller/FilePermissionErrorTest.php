<?php
 

namespace Modules\Core\Tests\Feature\Controller;

use Tests\TestCase;

class FilePermissionErrorTest extends TestCase
{
    public function test_file_permissions_can_be_viewed()
    {
        $this->signIn();

        $this->get('/errors/permissions')->assertOk();
    }
}
