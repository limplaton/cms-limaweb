<?php
 

namespace Modules\Translator\Tests\Feature;

use Illuminate\Support\Facades\File;
use Modules\Translator\App\Translator;
use Tests\TestCase;

class TranslationControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        foreach (['en_US', 'invalid-locale', '.custom/en_US', '.custom'] as $folder) {
            $path = lang_path($folder);

            if (is_dir($path)) {
                File::deepCleanDirectory($path);
            }
        }

        parent::tearDown();
    }

    public function test_unauthenticated_user_cannot_access_translation_endpoints()
    {
        $this->postJson('api/translation')->assertUnauthorized();
        $this->getJson('api/translation/FAKE_LOCALE')->assertUnauthorized();
        $this->putJson('api/translation/FAKE_LOCALE/FAKE_GROUP')->assertUnauthorized();
    }

    public function test_unauthorized_user_cannot_access_translation_endpoints()
    {
        $this->asRegularUser()->signIn();

        $this->postJson('api/translation')->assertForbidden();
        $this->getJson('api/translation/FAKE_LOCALE')->assertForbidden();
        $this->putJson('api/translation/FAKE_LOCALE/FAKE_GROUP')->assertForbidden();
    }

    public function test_user_can_retrieve_the_translations_for_locale()
    {
        $this->signIn();

        $this->getJson('/api/translation/en')->assertJsonStructure([
            'current', 'source',
        ]);
    }

    public function test_user_can_create_new_locale()
    {
        $this->signIn();

        $this->postJson('/api/translation', [
            'name' => 'en_US',
        ]);

        $this->assertDirectoryExists(lang_path('en_US'));
    }

    public function test_new_locale_requires_unique_a_name()
    {
        $this->signIn();

        $this->postJson('/api/translation', [
            'name' => '',
        ])->assertJsonValidationErrors(['name']);
    }

    public function test_new_locale_requires_unique_name()
    {
        $this->signIn();

        $this->postJson('/api/translation', [
            'name' => 'en',
        ])->assertJsonValidationErrors(['name']);
    }

    public function test_it_required_valid_locale()
    {
        $this->signIn();

        $this->postJson('/api/translation', [
            'name' => 'invalid-locale',
        ])->assertJsonValidationErrors(['name']);
    }

    public function test_user_can_update_translations_for_locale_group()
    {
        (new Translator)->createLocale('en_US');

        File::copy(base_path('tests/Fixtures/locale_group.php'), lang_path('en_US/locale_group.php'));

        $this->signIn();

        $this->putJson('/api/translation/en_US/locale_group', [
            'translations' => [
                'key' => 'changed',
                'deep.key' => 'changed',
            ],
        ])->assertNoContent();

        $translations = app('translation.loader')->load('en_US', 'locale_group');

        $this->assertEquals('changed', $translations['key']);
        $this->assertEquals('changed', $translations['deep']['key']);
    }

    public function test_it_can_update_translation_group_with_dot_notation_keys()
    {
        (new Translator)->createLocale('en_US');
        File::copy(base_path('tests/Fixtures/locale_group.php'), lang_path('en_US/locale_group.php'));

        $this->signIn();

        $this->putJson('/api/translation/en_US/locale_group', [
            'translations' => [
                'Sentence end. Another sentence start.' => 'Sentence end. Another sentence start.',
            ],
        ])->assertNoContent();

        $translations = app('translation.loader')->load('en_US', 'locale_group');
        $this->assertEquals('Sentence end. Another sentence start.', $translations['Sentence end. Another sentence start.']);
    }
}
