<?php
 

namespace Modules\Core\Tests\Feature\Controller\Api;

use Illuminate\Support\Facades\File;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Facades\MailableTemplates;
use Modules\Core\App\Models\MailableTemplate;
use Tests\Fixtures\SampleMailTemplate;
use Tests\TestCase;

class MailableTemplateControllerTest extends TestCase
{
    public function test_unauthenticated_user_cannot_access_mailable_templates_endpoints()
    {
        $this->getJson('/api/mailable-templates')->assertUnauthorized();
        $this->getJson('/api/mailable-templates/en/locale')->assertUnauthorized();
        $this->getJson('/api/mailable-templates/FAKE_ID')->assertUnauthorized();
        $this->putJson('/api/mailable-templates/FAKE_ID')->assertUnauthorized();
    }

    public function test_unauthorized_user_cannot_access_mailable_template_endpoints()
    {
        $this->asRegularUser()->signIn();

        MailableTemplates::seedForLocale('en');

        $template = MailableTemplate::first();

        $this->getJson('/api/mailable-templates')->assertForbidden();
        $this->getJson('/api/mailable-templates/en/locale')->assertForbidden();
        $this->getJson('/api/mailable-templates/'.$template->id)->assertForbidden();
        $this->putJson('/api/mailable-templates/'.$template->id)->assertForbidden();
    }

    public function test_user_can_retrieve_all_mailable_templates()
    {
        $this->signIn();

        MailableTemplates::forget()->register(SampleMailTemplate::class)->seed();

        $this->getJson('/api/mailable-templates')
            ->assertJsonCount(count(MailableTemplates::get()) * count(Innoclapps::locales()))
            ->assertJsonPath('0.name', SampleMailTemplate::name());
    }

    public function test_user_can_retrieve_mailable_templates_by_locale()
    {
        MailableTemplates::forget();

        $this->signIn();

        MailableTemplates::register(SampleMailTemplate::class)->seed();

        $this->getJson('/api/mailable-templates/en/locale')->assertJsonCount(1)->assertJsonPath('0.name', SampleMailTemplate::name());
    }

    public function test_user_can_retrieve_mailable_template()
    {
        MailableTemplates::forget();

        $this->signIn();

        MailableTemplates::register(SampleMailTemplate::class)->seed();

        $template = MailableTemplate::forMailable(SampleMailTemplate::class)->forLocale('en')->first();

        $this->getJson('/api/mailable-templates/'.$template->id)->assertJson(['name' => SampleMailTemplate::name()]);
    }

    public function test_user_can_update_mailable_template()
    {
        MailableTemplates::forget();

        $this->signIn();

        MailableTemplates::register(SampleMailTemplate::class)->seed();

        $template = MailableTemplate::forMailable(SampleMailTemplate::class)->forLocale('en')->first();

        $this->putJson('/api/mailable-templates/'.$template->id, $data = [
            'subject' => 'Changed Subject',
            'html_template' => 'Changed HTML Template',
            'text_template' => 'Changed Text Template',
        ])->assertJson($data);
    }

    protected function tearDown(): void
    {
        foreach (['en_TEST', 'fr_TEST'] as $locale) {
            $path = lang_path($locale);

            if (is_dir($path)) {
                File::deepCleanDirectory($path);
            }
        }

        parent::tearDown();
    }
}
