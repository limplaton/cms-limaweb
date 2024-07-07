<?php
 

namespace Modules\Core\Tests\Unit\Mail\Headers;

use Illuminate\Contracts\Support\Arrayable;
use Modules\Core\App\Common\Mail\Headers\Header;
use PHPUnit\Framework\TestCase;

class HeaderTest extends TestCase
{
    public function test_header_has_name()
    {
        $header = new Header('x--test', 'value');

        $this->assertSame('x--test', $header->getName());
    }

    public function test_header_name_is_aways_in_lowercase()
    {
        $header = new Header('X--Value', 'value');

        $this->assertSame('x--value', $header->getName());
    }

    public function test_header_has_value()
    {
        $header = new Header('x--test', 'value');

        $this->assertSame('value', $header->getValue());
    }

    public function test_header_is_arrayable()
    {
        $header = new Header('x--test', 'value');

        $this->assertInstanceOf(Arrayable::class, $header);

        $this->assertEquals([
            'name' => 'x--test',
            'value' => 'value',
        ], $header->toArray());
    }
}
