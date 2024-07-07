<?php
 

namespace Modules\MailClient\Tests\Feature;

use Modules\MailClient\App\Models\PredefinedMailTemplate;
use Tests\TestCase;

class PredefinedMailTemplateControllerTest extends TestCase
{
    public function test_unauthenticated_user_cannot_predefined_mail_templates_endpoints()
    {
        $template = PredefinedMailTemplate::factory()->create();

        $this->getJson('/api/mails/templates')->assertUnauthorized();
        $this->postJson('/api/mails/templates')->assertUnauthorized();
        $this->putJson('/api/mails/templates/'.$template->id)->assertUnauthorized();
        $this->getJson('/api/mails/templates/'.$template->id)->assertUnauthorized();
        $this->deleteJson('/api/mails/templates/'.$template->id)->assertUnauthorized();
    }

    public function test_user_can_create_new_predefined_mail_template()
    {
        $user = $this->signIn();

        $this->postJson('/api/mails/templates', [
            'name' => $name = 'Template Name',
            'subject' => $subject = 'Mail Subject',
            'body' => $body = '<div>Mail Body</div',
            'is_shared' => $isShared = true,
        ])->assertJson([
            'name' => $name,
            'subject' => $subject,
            'body' => $body,
            'is_shared' => $isShared,
            'user_id' => $user->id,
        ]);
    }

    public function test_predefined_mail_template_requires_name()
    {
        $this->signIn();

        $this->postJson('/api/mails/templates', ['name' => ''])->assertJsonValidationErrors('name');
        $this->putJson('/api/mails/templates/FAKE_ID', ['name' => ''])->assertJsonValidationErrors('name');
    }

    public function test_predefined_mail_template_name_must_be_unique()
    {
        $this->signIn();

        $template = PredefinedMailTemplate::factory()->create();

        $this->postJson('/api/mails/templates', ['name' => $template->name])
            ->assertJsonValidationErrors($errors = ['name' => 'The name has already been taken']);
        $this->putJson('/api/mails/templates/FAKE_ID', ['name' => $template->name])
            ->assertJsonValidationErrors($errors);
    }

    public function test_predefined_mail_template_requires_subject()
    {
        $this->signIn();

        $this->postJson('/api/mails/templates', ['subject' => ''])->assertJsonValidationErrors('subject');
        $this->putJson('/api/mails/templates/FAKE_ID', ['subject' => ''])->assertJsonValidationErrors('subject');
    }

    public function test_predefined_mail_template_requires_body()
    {
        $this->signIn();

        $this->postJson('/api/mails/templates', ['body' => ''])->assertJsonValidationErrors('body');
        $this->putJson('/api/mails/templates/FAKE_ID', ['body' => ''])->assertJsonValidationErrors('body');
    }

    public function test_properly_validates_predefined_mail_template_is_shared_attribute()
    {
        $this->signIn();

        $this->postJson('/api/mails/templates', ['is_shared' => ''])->assertJsonValidationErrors('is_shared');
        $this->putJson('/api/mails/templates/FAKE_ID', ['is_shared' => ''])->assertJsonValidationErrors('is_shared');
        $this->putJson('/api/mails/templates/FAKE_ID', ['is_shared' => 'not-bool'])->assertJsonValidationErrors('is_shared');
    }

    public function test_user_can_retrieve_predefined_mail_template()
    {
        $this->signIn();

        $template = PredefinedMailTemplate::factory()->create();

        $this->getJson('/api/mails/templates/'.$template->id)
            ->assertOk()
            ->assertJson(['id' => $template->id, 'name' => $template->name]);
    }

    public function tests_user_can_retrieve_and_see_predefined_mail_template_he_is_allowed_to_see()
    {
        $loggedInUser = $this->asRegularUser()->signIn();
        $user2 = $this->asRegularUser()->createUser();

        PredefinedMailTemplate::factory()->personal()->for($loggedInUser)->create();
        $template2 = PredefinedMailTemplate::factory()->personal()->for($user2)->create();

        $this->getJson('/api/mails/templates')
            ->assertJsonCount(1);

        $template3 = PredefinedMailTemplate::factory()->shared()->for($loggedInUser)->create();

        $this->getJson('/api/mails/templates')
            ->assertJsonCount(2);

        $this->getJson('/api/mails/templates/'.$template2->id)
            ->assertForbidden(2);

        $this->getJson('/api/mails/templates/'.$template3->id)
            ->assertOk()
            ->assertJson(['name' => $template3->name]);
    }

    public function test_authorized_user_can_update_predefined_mail_template()
    {
        $user = $this->signIn();
        $template = PredefinedMailTemplate::factory()->for($user)->create();

        $this->putJson('/api/mails/templates/'.$template->id, $attributes = [
            'name' => '--changed-name--',
            'subject' => '--changed-subject--',
            'body' => '--changed-body--',
            'is_shared' => false,
        ])->assertOk()
            ->assertJson($attributes);
    }

    public function test_unauthorized_user_cannot_update_predefined_mail_template()
    {
        $this->asRegularUser()->signIn();
        $otherUser = $this->createUser();
        $template = PredefinedMailTemplate::factory()->for($otherUser)->create();

        $this->putJson('/api/mails/templates/'.$template->id, [
            'name' => '--changed-name--',
            'subject' => '--changed-subject--',
            'body' => '--changed-body--',
            'is_shared' => false,
        ])->assertForbidden();
    }

    public function test_authorized_user_can_delete_predefined_mail_template()
    {
        $user = $this->asRegularUser()->signIn();
        $template = PredefinedMailTemplate::factory()->for($user)->create();

        $this->deleteJson('/api/mails/templates/'.$template->id)->assertStatus(204);
    }

    public function test_unauthorized_user_cannot_delete_predefined_mail_template()
    {
        $this->asRegularUser()->signIn();
        $otherUser = $this->createUser();
        $template = PredefinedMailTemplate::factory()->for($otherUser)->create();

        $this->deleteJson('/api/mails/templates/'.$template->id)->assertForbidden();
    }
}
