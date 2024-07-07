<?php
 

namespace Modules\Core\Tests\Feature\Macros\Str;

use Illuminate\Support\Str;
use Tests\TestCase;

class ClickableUrlsTest extends TestCase
{
    public function test_it_makes_urls_clickable()
    {
        $formatted = Str::clickable('Test https://crm.com Test');

        $this->assertStringContainsString('<a href="https://crm.com" rel="nofollow" target=\'_blank\'>https://crm.com</a>', $formatted);
    }

    public function test_it_makes_multiple_urls_clickable()
    {
        $formatted = Str::clickable('Test https://crm.com Test http://crm.com');

        $this->assertStringContainsString('<a href="https://crm.com" rel="nofollow" target=\'_blank\'>https://crm.com</a>', $formatted);
        $this->assertStringContainsString('<a href="http://crm.com" rel="nofollow" target=\'_blank\'>http://crm.com</a>', $formatted);
    }

    public function test_it_makes_ftp_clickable()
    {
        $formatted = Str::clickable('Test ftp://127.0.01 Test');

        $this->assertStringContainsString('<a href="ftp://127.0.01" rel="nofollow" target=\'_blank\'>ftp://127.0.01</a>', $formatted);
    }

    public function test_it_makes_email_clickable()
    {
        $formatted = Str::clickable('Test email@exampe.com Test');

        $this->assertStringContainsString('<a href="mailto:email@exampe.com">email@exampe.com</a>', $formatted);
    }
}
