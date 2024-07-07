<?php
 

namespace Modules\MailClient\Tests\Feature;

use Modules\MailClient\App\Models\PredefinedMailTemplate;
use Modules\Users\App\Models\User;
use Tests\TestCase;

class PredefinedMailTemplateTest extends TestCase
{
    public function test_predefined_mail_template_has_user()
    {
        $template = PredefinedMailTemplate::factory()->for(User::factory())->create();

        $this->assertInstanceOf(User::class, $template->user);
    }
}
