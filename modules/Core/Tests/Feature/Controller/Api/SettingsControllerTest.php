<?php
 

namespace Modules\Core\Tests\Feature\Controller\Api;

use Modules\Core\App\Settings\DefaultSettings;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    public function test_unauthenticated_user_cannot_access_settings_endpoints()
    {
        $this->postJson('/api/settings')->assertUnauthorized();
        $this->getJson('/api/settings')->assertUnauthorized();
    }

    public function test_unauthorized_cannot_access_settings_endpoint()
    {
        $this->asRegularUser()->signIn();

        $this->postJson('/api/settings')->assertForbidden();
        $this->getJson('/api/settings')->assertForbidden();
    }

    public function test_all_settings_can_be_retrieved()
    {
        $this->signIn();

        $this->getJson('/api/settings')->assertOk()->assertJson(settings()->all());
    }

    public function test_user_can_update_settings()
    {
        $this->signIn();

        // Create new test setting
        settings()->set('-test-key-', 'test-value')->save();

        $this->postJson('/api/settings', [
            '-test-key-' => 'updated-test-value',
        ])->assertOk();

        $this->assertEquals('updated-test-value', settings()->get('-test-key-'));
    }

    public function test_new_setting_is_created_when_not_exist_in_database_but_exist_in_request_payload()
    {
        $this->signIn();

        $this->postJson('/api/settings', [
            '-test-key-' => 'new-test-value',
        ])->assertOk();

        $this->assertEquals('new-test-value', settings()->get('-test-key-'));
    }

    public function test_required_settings_are_not_updated_when_empty_value_is_passed()
    {
        $this->signIn();

        $required = DefaultSettings::getRequired();

        $randomKey = array_rand($required);
        $valueBeforeToTryToUpdate = settings()->get($required[$randomKey]);

        $this->postJson('/api/settings', [
            $required[$randomKey] => '',
        ])->assertOk();

        $this->assertEquals($valueBeforeToTryToUpdate, settings()->get($required[$randomKey]));
    }
}
