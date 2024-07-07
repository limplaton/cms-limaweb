<?php
 

namespace Modules\Translator\Tests\Feature;

use Illuminate\Support\Facades\File;
use Modules\Translator\App\Contracts\TranslationLoader;
use Modules\Translator\App\Translator;
use Tests\TestCase;

class OverrideFileLoaderTest extends TestCase
{
    protected function tearDown(): void
    {
        foreach (['en_TEST', '.custom/en_TEST', '.custom', 'fr_TEST'] as $folder) {
            $path = lang_path($folder);

            if (is_dir($path)) {
                File::deepCleanDirectory($path);
            }
        }

        parent::tearDown();
    }

    public function test_it_can_load_the_overriden_translations()
    {
        $translator = new Translator;

        $translator->createLocale('en_TEST');
        $translator->createLocale('fr_TEST');

        File::copy(base_path('tests/Fixtures/locale_group.php'), lang_path('en_TEST/locale_group.php'));
        File::copy(base_path('tests/Fixtures/locale_group.php'), lang_path('fr_TEST/locale_group.php'));

        $translator->save('fr_TEST', 'locale_group', [
            'key' => 'changed',
            'deep' => [
                'key' => 'changed',
            ],
        ]);

        $loader = app(TranslationLoader::class);

        $translations = $loader->loadTranslations('fr_TEST', 'locale_group');

        $this->assertIsArray($translations);
        $this->assertCount(2, $translations);
        $this->assertArrayHasKey('key', $translations);
        $this->assertArrayHasKey('deep', $translations);
        $this->assertSame('changed', $translations['key']);
        $this->assertSame('changed', $translations['deep']['key']);
    }

    public function test_it_can_save_custom_translations()
    {
        $translator = new Translator;

        $translator->createLocale('en_TEST');

        File::copy(base_path('tests/Fixtures/locale_group.php'), lang_path('en_TEST/locale_group.php'));

        $translator->save('en_TEST', 'locale_group', [
            'key' => 'changed',
            'deep' => [
                'key' => 'changed',
            ],
        ]);

        $this->assertDirectoryExists(lang_path('.custom/en_TEST'));
        $this->assertFileExists(lang_path('.custom/en_TEST/locale_group.php'));
    }

    public function test_locale_has_original_translations()
    {
        $translator = new Translator;

        $translator->createLocale('en_TEST');
        $translator->createLocale('fr_TEST');

        File::copy(base_path('tests/Fixtures/locale_group.php'), lang_path('en_TEST/locale_group.php'));
        File::copy(base_path('tests/Fixtures/locale_group.php'), lang_path('fr_TEST/locale_group.php'));

        $translator = $translator->setFallbackLocale('en_TEST');

        $translator->save('fr_TEST', 'locale_group', [
            'key' => 'changed',
            'deep' => [
                'key' => 'changed',
            ],
        ]);

        $groups = $translator->source('fr_TEST')['groups'];

        $this->assertIsArray($groups);
        $this->assertArrayHasKey('locale_group', $groups);
        $this->assertCount(2, $groups['locale_group']);
        $this->assertArrayHasKey('key', $groups['locale_group']);
        $this->assertSame('value', $groups['locale_group']['key']);
    }
}
