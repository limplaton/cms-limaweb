<?php
 

namespace Modules\Core\Tests\Feature;

use Illuminate\Support\Facades\File;
use Modules\Core\App\Facades\MailableTemplates;
use Modules\Core\App\Models\MailableTemplate;
use Modules\Translator\App\Translator;
use Tests\Fixtures\SampleMailTemplate;
use Tests\TestCase;

class MailableTemplateTest extends TestCase
{
    public function test_mailable_template_is_seeded_when_new_mailable_exist()
    {
        MailableTemplates::register(SampleMailTemplate::class)->seed();

        $this->assertDatabaseHas('mailable_templates', [
            'name' => SampleMailTemplate::name(),
            'subject' => SampleMailTemplate::defaultSubject(),
            'html_template' => SampleMailTemplate::defaultHtmlTemplate(),
            'text_template' => SampleMailTemplate::defaultTextMessage(),
            'mailable' => SampleMailTemplate::class,
            'locale' => 'en',
        ]);
    }

    public function test_mailable_templates_are_seeded_when_new_locale_exist()
    {
        $translator = new Translator;
        $translator->createLocale('en_TEST');

        $total = count(MailableTemplates::get());

        MailableTemplates::seed();

        $this->assertCount($total, MailableTemplate::forLocale('en_TEST')->get());
    }

    public function test_mailable_templates_are_seeded_for_all_locales()
    {
        $translator = new Translator;
        $translator->createLocale('en_TEST');
        $translator->createLocale('fr_TEST');

        MailableTemplates::seed();

        $total = count(MailableTemplates::get());

        $this->assertCount($total, MailableTemplate::forLocale('en_TEST')->get());
        $this->assertCount($total, MailableTemplate::forLocale('fr_TEST')->get());
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
