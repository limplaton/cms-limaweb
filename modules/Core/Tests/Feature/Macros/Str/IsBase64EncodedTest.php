<?php
 

namespace Modules\Core\Tests\Feature\Macros\Str;

use Illuminate\Support\Str;
use Tests\TestCase;

class IsBase64EncodedTest extends TestCase
{
    public function test_can_check_whether_the_string_is_base64_encoded()
    {
        $this->assertTrue(Str::isBase64Encoded(base64_encode('test')));
        $this->assertTrue(Str::isBase64Encoded('PGEgaHJlZj0iIj5UZXN0PC9hPg=='));
        $this->assertFalse(Str::isBase64Encoded('-test-'));
        $this->assertFalse(Str::isBase64Encoded('Some text'));
    }
}
