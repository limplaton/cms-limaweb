<?php
 

namespace Modules\WebForms\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Modules\Contacts\App\Enums\PhoneType;
use Modules\Contacts\App\Models\Company;
use Modules\Contacts\App\Models\Contact;
use Modules\Contacts\App\Models\Phone;
use Modules\Contacts\App\Models\Source;
use Modules\Core\App\Fields\Field;
use Modules\Core\App\Fields\User;
use Modules\Core\App\Models\Country;
use Modules\Core\Database\Seeders\CountriesSeeder;
use Modules\Core\Database\Seeders\SettingsSeeder;
use Modules\Deals\App\Models\Deal;
use Modules\WebForms\App\Http\Requests\WebFormRequest;
use Modules\WebForms\App\Mail\WebFormSubmitted;
use Modules\WebForms\App\Models\WebForm;
use Modules\WebForms\App\Services\FormSubmissionService;
use Tests\TestCase;

class FormSubmissionServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        User::setAssigneer(null);
        Field::setRequest(null);

        parent::tearDown();
    }

    public function test_web_form_can_be_submitted()
    {
        $this->createWebFormSource();

        $this->seed([SettingsSeeder::class, CountriesSeeder::class]);

        Storage::fake();

        $form = WebForm::factory()
            ->addFieldSection('first_name', 'contacts', ['requestAttribute' => 'contact_first_name'])
            ->addFieldSection('last_name', 'contacts', ['requestAttribute' => 'contact_last_name'])
            ->addFieldSection('email', 'contacts', ['requestAttribute' => 'contact_email'])
            ->addFieldSection('phones', 'contacts', ['requestAttribute' => 'contact_phone'])
            ->addFieldSection('country_id', 'contacts', ['requestAttribute' => 'contact_country_id'])
            ->addFileSection('contacts', ['requestAttribute' => 'contacts_file'])

            ->addFieldSection('name', 'deals', ['requestAttribute' => 'deal_name'])
            ->addFieldSection('amount', 'deals', ['requestAttribute' => 'deal_amount'])
            ->addFieldSection('expected_close_date', 'deals', ['requestAttribute' => 'deal_expected_close_date'])
            ->addFileSection('deals', ['requestAttribute' => 'deals_file'])

            ->addFieldSection('name', 'companies', ['requestAttribute' => 'company_name'])
            ->addFieldSection('email', 'companies', ['requestAttribute' => 'company_email'])
            ->addFieldSection('domain', 'companies', ['requestAttribute' => 'company_domain'])
            ->addFileSection('companies', ['requestAttribute' => 'companies_file'])
            ->create();

        $this->submitForm($form, [
            'contact_first_name' => 'John',
            'contact_last_name' => 'Doe',
            'contact_email' => 'john@example.com',
            'contact_country_id' => $countryId = Country::first()->getKey(),
            'contact_phone' => [['number' => '+1547-7745-55', 'type' => 'work']],
            'contacts_file' => UploadedFile::fake()->image('contacts_photo.jpg'),

            'deal_name' => 'Deal',
            'deal_amount' => 1250,
            'deal_expected_close_date' => '2021-02-12',
            'deals_file' => UploadedFile::fake()->image('deals_photo.jpg'),

            'company_name' => 'KONKORD DIGITAL',
            'company_email' => 'konkord@example.com',
            'company_domain' => 'crm.com',
            'companies_file' => UploadedFile::fake()->image('companies_photo.jpg'),
        ]);

        $this->assertDatabaseHas('deals', [
            'name' => 'Deal',
            'amount' => 1250,
            'web_form_id' => $form->id,
            'expected_close_date' => '2021-02-12',
            'user_id' => $form->user_id,
            'pipeline_id' => $form->submit_data['pipeline_id'],
            'stage_id' => $form->submit_data['stage_id'],
        ]);

        $deal = Deal::first();
        $this->assertEquals('deals_photo', $deal->media->first()->filename);

        $this->assertDatabaseHas('contacts', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'country_id' => $countryId,
            'email' => 'john@example.com',
            'user_id' => $form->user_id,
            'source_id' => Source::findByFlag('web-form')->id,
        ]);
        $this->assertCount(1, $deal->contacts);
        $contact = Contact::first();
        $this->assertEquals('contacts_photo', $contact->media->first()->filename);

        $this->assertDatabaseHas('phones', [
            'type' => PhoneType::work,
            'number' => '+1547-7745-55',
            'phoneable_id' => $contact->id,
            'phoneable_type' => Contact::class,
        ]);

        $this->assertDatabaseHas('companies', [
            'name' => 'KONKORD DIGITAL',
            'email' => 'konkord@example.com',
            'domain' => 'crm.com',
            'source_id' => Source::findByFlag('web-form')->id,
        ]);
        $this->assertCount(1, $deal->companies);
        $company = Company::first();
        $this->assertEquals('companies_photo', $company->media->first()->filename);
    }

    public function test_it_sets_the_owner_assigned_date_attribute()
    {
        $this->createWebFormSource();

        $this->seed([SettingsSeeder::class, CountriesSeeder::class]);

        Storage::fake();

        $form = WebForm::factory()
            ->addFieldSection('first_name', 'contacts', ['requestAttribute' => 'contact_first_name'])
            ->addFieldSection('email', 'contacts', ['requestAttribute' => 'contact_email'])

            ->addFieldSection('name', 'deals', ['requestAttribute' => 'deal_name'])

            ->addFieldSection('name', 'companies', ['requestAttribute' => 'company_name'])
            ->create();

        $this->submitForm($form, [
            'contact_first_name' => 'John',
            'contact_email' => 'email@example.com',

            'deal_name' => 'Deal',

            'company_name' => 'KONKORD DIGITAL',
        ]);

        $this->assertNotNull(Deal::first()->owner_assigned_date);
        $this->assertNotNull(Contact::first()->owner_assigned_date);
        $this->assertNotNull(Company::first()->owner_assigned_date);
    }

    public function test_it_send_notifications_when_form_is_submitted()
    {
        $this->createWebFormSource();

        $form = WebForm::factory()
            ->addFieldSection('email', 'contacts', ['requestAttribute' => 'email'])
            ->create(['notifications' => ['john@example.com', 'doe@example.com']]);

        Mail::fake();

        $this->submitForm($form, ['email' => 'email@example.com']);

        Mail::assertQueued(function (WebFormSubmitted $mail) {
            return $mail->hasTo('john@example.com');
        });

        Mail::assertQueued(function (WebFormSubmitted $mail) {
            return $mail->hasTo('doe@example.com');
        });

        Mail::assertQueued(WebFormSubmitted::class, 2);
    }

    public function test_it_does_not_send_notifications_when_no_emails_provided()
    {
        $this->createWebFormSource();

        $form = WebForm::factory()
            ->addFieldSection('email', 'contacts', ['requestAttribute' => 'email'])
            ->create(['notifications' => []]);

        Mail::fake();

        $this->submitForm($form, ['email' => 'email@example.com']);

        Mail::assertNothingSent();
    }

    public function test_it_does_not_create_company_when_the_web_form_doesnt_have_company_fields()
    {
        $this->createWebFormSource();

        $form = WebForm::factory()
            ->addFieldSection('email', 'contacts', ['requestAttribute' => 'email'])
            ->create();

        $this->submitForm($form, ['email' => 'email@example.com']);

        $this->assertDatabaseCount('companies', 0);
    }

    public function test_it_uses_contact_phone_number_as_first_name_when_doesnt_have_first_name_field()
    {
        $this->createWebFormSource();

        $form = WebForm::factory()
            ->addFieldSection('phones', 'contacts', ['requestAttribute' => 'contact_phone'])
            ->create();

        $this->submitForm($form, [
            'contact_phone' => [['number' => '+1547-7745-55', 'type' => 'work']],
        ]);

        $this->assertDatabaseHas('contacts', [
            'first_name' => '+1547-7745-55',
        ]);
    }

    public function test_it_uses_contact_first_name_value_when_there_is_no_deal_name_field()
    {
        $this->createWebFormSource();

        $form = WebForm::factory()
            ->addFieldSection('first_name', 'contacts', ['requestAttribute' => 'contact_first_name'])
            ->create();

        $this->submitForm($form, [
            'contact_first_name' => 'John',
        ]);

        $this->assertDatabaseHas('deals', [
            'name' => 'John Deal',
        ]);
    }

    public function test_it_uses_contact_first_name_value_when_there_is_no_company_name_field()
    {
        $this->createWebFormSource();

        $form = WebForm::factory()
            ->addFieldSection('first_name', 'contacts', ['requestAttribute' => 'contact_first_name'])
            ->addFieldSection('domain', 'companies', ['requestAttribute' => 'company_domain'])
            ->create();

        $this->submitForm($form, [
            'contact_first_name' => 'John',
            'company_domain' => 'crm.com',
        ]);

        $this->assertDatabaseHas('companies', [
            'name' => 'John Company',
        ]);
    }

    public function test_deal_prefix_is_added()
    {
        $this->createWebFormSource();

        $form = WebForm::factory()
            ->addFieldSection('email', 'contacts', ['requestAttribute' => 'contact_email'])
            ->addFieldSection('name', 'deals', ['requestAttribute' => 'deal_name'])
            ->create(['title_prefix' => 'PREFIX-']);

        $this->submitForm($form, [
            'contact_email' => 'john@example.com',
            'deal_name' => 'Deal Name',
        ]);

        $this->assertDatabaseHas('deals', [
            'name' => 'PREFIX-Deal Name',
        ]);
    }

    public function test_deal_prefix_is_added_when_name_field_does_not_exists()
    {
        $this->createWebFormSource();

        $form = WebForm::factory()
            ->addFieldSection('email', 'contacts', ['requestAttribute' => 'contact_email'])
            ->create(['title_prefix' => 'PREFIX-']);

        $this->submitForm($form, [
            'contact_email' => 'john@example.com',
        ]);

        $this->assertDatabaseHas('deals', [
            'name' => 'PREFIX-john@example.com Deal',
        ]);
    }

    public function test_it_updates_the_contact_if_exists_by_email()
    {
        $this->createWebFormSource();

        Contact::factory()->create(['first_name' => 'John', 'email' => 'john@example.com']);

        $form = WebForm::factory()
            ->addFieldSection('email', 'contacts', ['requestAttribute' => 'email'])
            ->addFieldSection('first_name', 'contacts', ['requestAttribute' => 'first_name'])
            ->create();

        $this->submitForm($form, [
            'email' => 'john@example.com',
            'first_name' => 'Updated First Name',
        ]);

        $this->assertDatabaseHas('contacts', ['first_name' => 'Updated First Name']);
        $this->assertDatabaseCount('contacts', 1);
    }

    public function test_it_updates_the_contact_if_exists_by_phone()
    {
        $this->createWebFormSource();
        $this->seed(CountriesSeeder::class);

        $contact = Contact::factory()->has(Phone::factory())->create(['first_name' => 'John']);
        $number = $contact->phones->first()->number;

        $form = WebForm::factory()
            ->addFieldSection('first_name', 'contacts', ['requestAttribute' => 'first_name'])
            ->addFieldSection('phones', 'contacts', ['requestAttribute' => 'phones'])
            ->create();

        $this->submitForm($form, [
            'first_name' => 'Jake',
            'phones' => [['number' => $number, 'type' => 'work']],
        ]);

        $this->assertDatabaseHas('contacts', ['first_name' => 'Jake']);
        $this->assertDatabaseCount('contacts', 1);
    }

    public function test_it_does_not_update_the_contact_user_id_when_exists()
    {
        $this->createWebFormSource();
        $user = $this->createUser();
        Contact::factory()->for($user)->create(['email' => 'john@example.com']);

        $form = WebForm::factory()
            ->addFieldSection('email', 'contacts', ['requestAttribute' => 'email'])
            ->create();

        $this->submitForm($form, ['email' => 'changed@example.com']);

        $this->assertDatabaseHas('contacts', ['user_id' => $user->id]);
    }

    public function test_it_updates_the_company_if_exists_by_email()
    {
        $this->createWebFormSource();

        Company::factory()->create(['email' => 'konkord@example.com', 'domain' => 'old.com']);

        $form = WebForm::factory()
            ->addFieldSection('first_name', 'contacts', ['requestAttribute' => 'first_name'])
            ->addFieldSection('email', 'companies', ['requestAttribute' => 'company_email'])
            ->addFieldSection('domain', 'companies', ['requestAttribute' => 'company_domain'])
            ->create();

        $this->submitForm($form, [
            'first_name' => 'John',
            'company_email' => 'konkord@example.com',
            'company_domain' => 'new.com',
        ]);

        $this->assertDatabaseHas('companies', ['domain' => 'new.com']);
        $this->assertDatabaseCount('companies', 1);
    }

    public function test_it_does_not_update_the_company_user_id_when_exists()
    {
        $this->createWebFormSource();
        $user = $this->createUser();
        Company::factory()->for($user)->create(['email' => 'konkord@example.com']);

        $form = WebForm::factory()
            ->addFieldSection('first_name', 'contacts', ['requestAttribute' => 'first_name'])
            ->addFieldSection('email', 'companies', ['requestAttribute' => 'company_email'])
            ->create();

        $this->submitForm($form, [
            'first_name' => 'John',
            'company_email' => 'konkord@example.com',
        ]);

        $this->assertDatabaseHas('companies', ['user_id' => $user->id]);
    }

    public function test_it_merges_duplicate_contact_phone_numbers()
    {
        $this->createWebFormSource();
        $this->seed(CountriesSeeder::class);

        $contact = Contact::factory()->has(
            Phone::factory(['number' => '+1555-555-555', 'type' => PhoneType::work->value])
        )->create(['first_name' => 'John',  'email' => $email = 'email@example.com']);

        $form = WebForm::factory()
            ->addFieldSection('first_name', 'contacts', ['requestAttribute' => 'first_name'])
            ->addFieldSection('email', 'contacts', ['requestAttribute' => 'email'])
            ->addFieldSection('phones', 'contacts', ['requestAttribute' => 'phones'])
            ->create();

        $this->submitForm($form, [
            'first_name' => 'Jake',
            'email' => $email,
            'phones' => [
                ['number' => '+1555-555-555', 'type' => 'mobile'],
                ['number' => '+1555-666-666', 'type' => 'work'],
            ],
        ]);

        $this->assertCount(2, $contact->phones);
    }

    public function test_it_merges_duplicate_company_phone_numbers()
    {
        $this->createWebFormSource();
        $this->seed(CountriesSeeder::class);

        $company = Company::factory()->has(
            Phone::factory(['number' => '+1555-555-555', 'type' => PhoneType::work->value])
        )->create(['name' => 'Acme']);

        $form = WebForm::factory()
            ->addFieldSection('email', 'contacts', ['requestAttribute' => 'contact_email'])
            ->addFieldSection('name', 'companies', ['requestAttribute' => 'company_name'])
            ->addFieldSection('phones', 'companies', ['requestAttribute' => 'phones'])
            ->create();

        $this->submitForm($form, [
            'contact_email' => 'email@example.com',
            'company_name' => 'Acme',
            'phones' => [
                ['number' => '+1555-555-555', 'type' => 'mobile'],
                ['number' => '+1555-666-666', 'type' => 'work'],
            ],
        ]);

        $this->assertCount(2, $company->phones);
    }

    protected function createWebFormSource()
    {
        Source::factory()->create(['name' => 'Web Form', 'flag' => 'web-form']);
    }

    protected function submitForm($form, $attributes = [])
    {
        $request = $this->newSubmissionRequest($form, $attributes);

        return (new FormSubmissionService())->submit($request);
    }

    protected function newSubmissionRequest($form, $attributes = [])
    {
        /** @var \Modules\WebForms\App\Http\Requests\WebFormRequest */
        $request = app(WebFormRequest::class);

        Field::setRequest($request);

        $routeResolver = function () use ($form, $request) {
            $route = new Route('POST', '/forms/f/{uuid}', []);
            $route->bind($request);
            $route->setParameter('uuid', $form->uuid);

            return $route;
        };

        $request->setRouteResolver($routeResolver)->merge($attributes);

        $request->performValidation();

        return $request->rememberFormInput();
    }
}
