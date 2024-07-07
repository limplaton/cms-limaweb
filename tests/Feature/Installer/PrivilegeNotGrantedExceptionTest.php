<?php

namespace Tests\Feature\Installer;

use App\Installer\PrivilegeNotGrantedException;
use Tests\TestCase;

class PrivilegeNotGrantedExceptionTest extends TestCase
{
    public function test_it_correctly_extracts_privilege_name_from_message()
    {
        $message = '12345 SELECT command denied';
        $exception = new PrivilegeNotGrantedException($message);

        $this->assertEquals('SELECT', $exception->getPriviligeName(), 'The privilege name should be extracted correctly from the exception message');
    }
}
