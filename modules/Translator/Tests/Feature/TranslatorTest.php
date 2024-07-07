<?php
 

namespace Modules\Translator\Tests\Feature;

use Illuminate\Support\Facades\File;
use Modules\Translator\App\Translator;
use Tests\TestCase;

class TranslatorTest extends TestCase
{
    protected function tearDown(): void
    {
        if (is_dir(lang_path('en_TEST'))) {
            File::cleanDirectory(lang_path('en_TEST'));
            rmdir(lang_path('en_TEST'));
        }

        parent::tearDown();
    }

    public function test_it_can_generate_json_language_file()
    {
        $path = config('translator.json');

        if (file_exists($path) && ! unlink($path)) {
            $this->markTestSkipped('Failed to remove the language file.');
        }

        Translator::generateJsonLanguageFile();

        $this->assertFileExists($path);
    }

    public function test_it_can_create_new_locale()
    {
        $translator = new Translator();

        $translator->createLocale('en_TEST');

        $this->assertDirectoryExists(lang_path('en_TEST'));
        $this->assertDirectoryIsReadable(lang_path('en_TEST'));
        $this->assertCount(count(File::files(lang_path('en'))), File::files(lang_path('en_TEST')));
    }
}
