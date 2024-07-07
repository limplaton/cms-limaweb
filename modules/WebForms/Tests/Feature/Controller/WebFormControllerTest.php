<?php
 

namespace Modules\WebForms\Tests\Feature\Controller;

use Modules\Contacts\App\Models\Source;
use Modules\Core\App\Fields\User;
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

    public function test_it_fails_when_form_does_not_exists()
    {
        $this->get(route('webform.view', 'invalid-uuid'))->assertNotFound();
    }

    public function test_inactive_form_can_be_viewed_when_there_is_logged_in_user()
    {
        $this->signIn();

        $form = WebForm::factory()->inactive()->create();

        $this->get($form->publicUrl)->assertViewIs('webforms::view');
    }

    public function test_inactive_form_cant_be_viewed_when_there_is_no_logged_in_user()
    {
        $form = WebForm::factory()->inactive()->create();

        $this->get($form->publicUrl)->assertNotFound();
    }

    public function test_it_uses_the_web_form_title()
    {
        $form = WebForm::factory()->create();

        $this->get($form->publicUrl)->assertSee('<title>'.$form->title.'</title>', false);
    }

    public function test_web_form_can_be_submitted()
    {
        Source::factory()->create(['name' => 'Web Form', 'flag' => 'web-form']);

        $form = WebForm::factory()
            ->addFieldSection('first_name', 'contacts', ['requestAttribute' => 'first_name', 'isRequired' => true])
            ->addFieldSection('last_name', 'contacts', ['requestAttribute' => 'last_name'])
            ->addFieldSection('email', 'contacts', ['requestAttribute' => 'email'])
            ->create();

        $this->post($form->publicUrl, [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ])->assertNoContent();
    }

    public function test_it_adds_the_field_to_web_form_sections()
    {
        Source::factory()->create(['name' => 'Web Form', 'flag' => 'web-form']);

        $form = WebForm::factory()
            ->withIntroductionSection()
            ->addFieldSection('first_name', 'contacts', ['requestAttribute' => 'first_name'])
            ->addFieldSection('last_name', 'contacts', ['requestAttribute' => 'last_name'])
            ->create();

        $response = $this->get($form->publicUrl);
        $fieldSections = $response->viewData('form')->resource->sections(WebFormSection::FIELD);

        $this->assertArrayHasKey('field', $fieldSections[0]);
        $this->assertArrayHasKey('field', $fieldSections[1]);
        $this->assertSame('first_name', $fieldSections[0]['field']['attribute']);
        $this->assertSame('last_name', $fieldSections[1]['field']['attribute']);
    }

    public function test_web_form_required_fields_are_validated()
    {
        Source::factory()->create(['name' => 'Web Form', 'flag' => 'web-form']);

        $form = WebForm::factory()
            ->addFieldSection('first_name', 'contacts', ['requestAttribute' => 'first_name', 'isRequired' => true])
            ->addFieldSection('last_name', 'contacts', ['label' => 'contact_last_name', 'requestAttribute' => 'last_name'])
            ->addFieldSection('email', 'contacts', ['label' => 'contact_email', 'isRequired' => true, 'requestAttribute' => 'email'])
            ->withSubmitButtonSection()
            ->create();

        $this->post($form->publicUrl)
            ->assertSessionHasErrors(['first_name', 'email'])
            ->assertSessionDoesntHaveErrors('last_name');
    }
}
