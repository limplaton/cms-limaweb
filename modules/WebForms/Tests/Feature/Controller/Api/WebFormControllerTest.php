<?php
 

namespace Modules\WebForms\Tests\Feature\Controller\Api;

use Modules\Core\App\Fields\User;
use Modules\Deals\App\Models\Pipeline;
use Modules\Deals\App\Models\Stage;
use Modules\WebForms\App\Enums\WebFormSection;
use Modules\WebForms\App\Models\WebForm;
use Tests\TestCase;

class WebFormControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        User::setAssigneer(null);

        parent::tearDown();
    }

    public function test_unauthenticated_user_cannot_access_mailable_templates_endpoints()
    {
        $form = WebForm::factory()->create();

        $this->getJson('/api/forms')->assertUnauthorized();
        $this->getJson('/api/forms/'.$form->id)->assertUnauthorized();
        $this->postJson('/api/forms')->assertUnauthorized();
        $this->putJson('/api/forms/'.$form->id)->assertUnauthorized();
        $this->deleteJson('/api/forms/'.$form->id)->assertUnauthorized();
    }

    public function test_unauthorized_user_cannot_access_mailable_template_endpoints()
    {
        $form = WebForm::factory()->create();

        $this->asRegularUser()->signIn();

        $this->getJson('/api/forms')->assertForbidden();
        $this->getJson('/api/forms/'.$form->id)->assertForbidden();
        $this->postJson('/api/forms')->assertForbidden();
        $this->putJson('/api/forms/'.$form->id)->assertForbidden();
        $this->deleteJson('/api/forms/'.$form->id)->assertForbidden();
    }

    public function test_user_can_fetch_web_forms()
    {
        $this->signIn();

        $forms = WebForm::factory(5)->create();

        $this->getJson('/api/forms')
            ->assertJsonCount(5)
            ->assertJsonFragment(['title' => $forms->first()->title]);
    }

    public function test_user_can_fetch_web_form()
    {
        $this->signIn();

        $form = WebForm::factory()->create();

        $this->getJson('/api/forms/'.$form->id)
            ->assertJsonFragment(['title' => $form->title]);
    }

    public function test_user_can_delete_web_form()
    {
        $this->signIn();

        $form = WebForm::factory()->create();

        $this->deleteJson('/api/forms/'.$form->id)->assertNoContent();
        $this->assertDatabaseEmpty('web_forms');
    }

    public function test_user_can_create_web_form()
    {
        $user = $this->signIn();
        $pipeline = Pipeline::factory()->withStages()->create();
        $stage = $pipeline->stages->first();

        $payload = [
            'title' => 'Web Form',
            'status' => 'active',
            'locale' => 'en',
            'user_id' => $user->id,
            'styles' => [
                'primary_color' => '#333',
                'background_color' => '#232356',
            ],
            'notifications' => ['john@example.com'],
            'submit_data' => [
                'pipeline_id' => $pipeline->id,
                'stage_id' => $stage->id,
                'action' => 'message',
                'success_title' => 'Form submitted.',
            ],
            'sections' => [
                [
                    'title' => 'Introduction Title',
                    'message' => 'Introduction Message',
                    'type' => WebFormSection::INTRODUCTION->value,
                ],
            ],
        ];

        $this->postJson('/api/forms', $payload)->assertJson($payload);
    }

    public function test_user_can_create_web_form_only_by_providing_title_and_styles()
    {
        $this->signIn();

        Pipeline::factory()->primary()->withStages()->create();

        $this->postJson('/api/forms', $payload = [
            'title' => 'Web Form',
            'styles' => ['primary_color' => '#333333', 'background_color' => '#333333'],
        ])
            ->assertJson($payload)
            ->assertJson(['sections' => [], 'notifications' => []]);
    }

    public function test_defaults_are_merged_on_web_form_creation()
    {
        $user = $this->signIn();

        $pipeline = Pipeline::factory()->primary()->withStages()->create();

        $this->postJson('/api/forms', [
            'title' => 'Web Form',
            'styles' => ['primary_color' => '#333333', 'background_color' => '#333333'],
        ])
            ->assertJson([
                'sections' => [],
                'notifications' => [],
                'submit_data' => [
                    'pipeline_id' => $pipeline->id,
                    'action' => 'message',
                    'success_title' => 'Form submitted.',
                ],
                'status' => 'active',
                'locale' => $user->preferredLocale(),
                'user_id' => $user->id,
            ]);
    }

    public function test_web_form_requires_title()
    {
        $this->signIn();

        $this->postJson('/api/forms', [
            'title' => '',
        ])
            ->assertJsonValidationErrors('title');

        $form = WebForm::factory()->create();

        $this->putJson('/api/forms/'.$form->getKey(), [
            'title' => '',
        ])
            ->assertJsonValidationErrors('title');
    }

    public function test_web_form_requires_styles_on_creation()
    {
        $this->signIn();

        $this->postJson('/api/forms', [
            'styles' => [],
        ])
            ->assertJsonValidationErrors(['styles.primary_color', 'styles.background_color']);

        $form = WebForm::factory()->create();

        $this->putJson('/api/forms/'.$form->getKey(), [
            'title' => 'Changed Title',
        ])
            ->assertJsonMissingValidationErrors(['styles.primary_color', 'styles.background_color']);
    }

    public function test_web_form_requires_valid_emails_for_notifications()
    {
        $this->signIn();

        $this->postJson('/api/forms', [
            'title' => 'Web Form',
            'styles' => ['primary_color' => 'red', 'background_color' => 'white'],
            'notifications' => ['invalid-email'],
        ])
            ->assertJsonValidationErrors(['notifications.0' => 'Enter valid email address']);
    }

    public function test_web_form_submit_data_action_is_validated()
    {
        $this->signIn();

        $this->postJson('/api/forms', WebForm::factory()->mergeSubmitData([
            'action' => 'not-valid-action',
        ])->make()->toArray())
            ->assertJsonValidationErrors('submit_data.action');
    }

    public function test_web_form_success_message_is_clean()
    {
        $this->signIn();

        $form = WebForm::factory()->mergeSubmitData([
            'success_message' => '<script>alert("OK")</script>Message',
        ])->create();

        $this->getJson('/api/forms/'.$form->getKey())->assertJsonPath(
            'submit_data.success_message', 'Message',
        );
    }

    public function test_web_form_field_section_label_is_clean()
    {
        $this->signIn();

        $form = WebForm::factory()
            ->addFieldSection('first_name', 'contacts', ['label' => '<script>alert("OK")</script>Label'])
            ->create();

        $this->getJson('/api/forms/'.$form->getKey())->assertJsonPath(
            'sections.0.label', 'Label',
        );
    }

    public function test_web_form_file_section_label_is_clean()
    {
        $this->signIn();

        $form = WebForm::factory()
            ->addFileSection('contacts', ['label' => '<script>alert("OK")</script>Label'])
            ->create();

        $this->getJson('/api/forms/'.$form->getKey())->assertJsonPath(
            'sections.0.label', 'Label',
        );
    }

    public function test_web_form_introduction_section_message_is_clean()
    {
        $this->signIn();

        $form = WebForm::factory()
            ->withIntroductionSection(['message' => '<script>alert("OK")</script>Message'])
            ->create();

        $this->getJson('/api/forms/'.$form->getKey())->assertJsonPath(
            'sections.0.message', 'Message',
        );
    }

    public function test_web_form_message_section_is_clean()
    {
        $this->signIn();

        $form = WebForm::factory()
            ->withMessageSection('<script>alert("OK")</script>Message')
            ->create();

        $this->getJson('/api/forms/'.$form->getKey())->assertJsonPath(
            'sections.0.message', 'Message',
        );
    }

    public function test_web_form_requires_redirect_url_if_submit_action_is_redirect()
    {
        $this->signIn();

        $this->postJson('/api/forms', WebForm::factory()->mergeSubmitData([
            'action' => 'redirect',
        ])->make()->toArray())
            ->assertJsonValidationErrors('submit_data.success_redirect_url');
    }

    public function test_web_form_requires_valid_redirect_url_when_submit_action_is_redirect()
    {
        $this->signIn();

        $this->postJson('/api/forms', WebForm::factory()->mergeSubmitData([
            'action' => 'redirect',
            'success_redirect_url' => 'not-a-url',
        ])->make()->toArray())
            ->assertJsonValidationErrors('submit_data.success_redirect_url');
    }

    public function test_web_form_requires_success_title_if_submit_action_is_message()
    {
        $this->signIn();

        $this->postJson('/api/forms', WebForm::factory()->mergeSubmitData([
            'action' => 'message',
            'success_title' => '',
        ])->make()->toArray())
            ->assertJsonValidationErrors('submit_data.success_title');
    }

    public function test_web_form_can_be_updated()
    {
        $this->signIn();

        $pipeline = Pipeline::factory()->create();
        $stage = Stage::factory()->for($pipeline)->create();
        $form = WebForm::factory()->create();

        $this->putJson('/api/forms/'.$form->getKey(), $changedData = [
            'title' => 'Changed Title',
            'status' => 'inactive',
            'locale' => 'en',
            'styles' => [
                'primary_color' => '#2f46e1',
                'background_color' => '#F324F1',
            ],
            'notifications' => ['changed@example.com', 'john@change.com'],
            'submit_data' => [
                'pipeline_id' => $pipeline->getKey(),
                'stage_id' => $stage->getKey(),
                'action' => 'redirect',
                'success_redirect_url' => 'https://crm.com',
                'success_title' => 'Thank you for submitting this form - viaTest.',
            ],
        ])->assertJson($changedData);
    }

    public function test_web_form_does_not_validate_locale_when_not_provided()
    {
        $this->signIn();

        $form = WebForm::factory()->create();

        $this->putJson('/api/forms/'.$form->getKey(), [
            'title' => 'Changed Title',
        ])->assertJsonMissingValidationErrors('locale');
    }

    public function test_web_form_does_not_validate_action_when_not_provided()
    {
        $this->signIn();

        $form = WebForm::factory()->create();

        $this->putJson('/api/forms/'.$form->getKey(), [
            'title' => 'Changed Title',
        ])->assertJsonMissingValidationErrors('submit_data.action');
    }

    public function test_web_form_does_not_validate_pipeline_when_not_provided()
    {
        $this->signIn();

        $form = WebForm::factory()->create();

        $this->putJson('/api/forms/'.$form->getKey(), [
            'title' => 'Changed Title',
        ])->assertJsonMissingValidationErrors('submit_data.pipeline_id');
    }

    public function test_web_form_does_not_validate_stage_when_not_provided()
    {
        $this->signIn();

        $form = WebForm::factory()->create();

        $this->putJson('/api/forms/'.$form->getKey(), [
            'title' => 'Changed Title',
        ])->assertJsonMissingValidationErrors('submit_data.stage_id');
    }

    public function test_web_form_pipeline_and_stage_are_automatically_taken_from_the_primary_pipeline_when_not_provided()
    {
        $this->signIn();

        $pipeline = Pipeline::factory()->primary()->withStages()->create();

        $form = WebForm::factory()->make(['submit_data' => []])->toArray();

        $this->postJson('/api/forms', $form)->assertJson([
            'submit_data' => [
                'pipeline_id' => $pipeline->getKey(),
                'stage_id' => $pipeline->stages->first()->getKey(),
            ],
        ]);
    }
}
