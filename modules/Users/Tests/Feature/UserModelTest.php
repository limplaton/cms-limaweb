<?php
 

namespace Modules\Users\Tests\Feature;

use Modules\Activities\App\Models\Activity;
use Modules\Contacts\App\Models\Company;
use Modules\Contacts\App\Models\Contact;
use Modules\Deals\App\Models\Deal;
use Modules\MailClient\App\Models\PredefinedMailTemplate;
use Modules\Users\App\Models\User;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    public function test_user_has_companies()
    {
        $user = User::factory()->has(Company::factory()->count(2))->create();

        $this->assertCount(2, $user->companies);
    }

    public function test_user_has_contacts()
    {
        $user = User::factory()->has(Contact::factory()->count(2))->create();

        $this->assertCount(2, $user->contacts);
    }

    public function test_user_has_deals()
    {
        $user = User::factory()->has(Deal::factory()->count(2))->create();

        $this->assertCount(2, $user->deals);
    }

    public function test_user_has_activities()
    {
        $user = User::factory()->has(Activity::factory()->count(2))->create();

        $this->assertCount(2, $user->activities);
    }

    public function test_user_has_predefined_mail_templates()
    {
        $user = User::factory()->has(PredefinedMailTemplate::factory()->count(2))->create();

        $this->assertCount(2, $user->predefinedMailTemplates);
    }
}
