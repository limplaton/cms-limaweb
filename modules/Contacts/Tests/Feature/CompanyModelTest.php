<?php
 

namespace Modules\Contacts\Tests\Feature;

use Modules\Activities\App\Models\Activity;
use Modules\Calls\App\Models\Call;
use Modules\Contacts\App\Models\Company;
use Modules\Contacts\App\Models\Contact;
use Modules\Contacts\App\Models\Phone;
use Modules\Contacts\App\Models\Source;
use Modules\Core\App\Models\Country;
use Modules\Core\Database\Seeders\CountriesSeeder;
use Modules\Deals\App\Models\Deal;
use Modules\Notes\App\Models\Note;
use Modules\Users\App\Models\User;
use Tests\TestCase;

class CompanyModelTest extends TestCase
{
    public function test_when_company_created_by_not_provided_uses_current_user_id()
    {
        $user = $this->signIn();

        $company = Company::factory(['created_by' => null])->create();

        $this->assertEquals($company->created_by, $user->id);
    }

    public function test_company_created_by_can_be_provided()
    {
        $user = $this->createUser();

        $company = Company::factory()->for($user, 'creator')->create();

        $this->assertEquals($company->created_by, $user->id);
    }

    public function test_company_has_path()
    {
        $company = Company::factory()->create();

        $this->assertEquals('/companies/'.$company->id, $company->path());
    }

    public function test_company_has_display_name_attribute()
    {
        $company = Company::factory(['name' => 'Company name'])->make();

        $this->assertEquals('Company name', $company->displayName());
    }

    public function test_company_has_country()
    {
        $this->seed(CountriesSeeder::class);

        $company = Company::factory()->for(Country::first())->create();

        $this->assertInstanceOf(Country::class, $company->country);
    }

    public function test_company_has_user()
    {
        $company = Company::factory()->for(User::factory())->create();

        $this->assertInstanceOf(User::class, $company->user);
    }

    public function test_company_has_source()
    {
        $company = Company::factory()->for(Source::factory())->create();

        $this->assertInstanceOf(Source::class, $company->source);
    }

    public function test_company_has_deals()
    {
        $company = Company::factory()->has(Deal::factory()->count(2))->create();

        $this->assertCount(2, $company->deals);
    }

    public function test_company_has_phones()
    {
        $this->seed(CountriesSeeder::class);

        $company = Company::factory()->has(Phone::factory()->count(2))->create();

        $this->assertCount(2, $company->phones);
    }

    public function test_company_has_calls()
    {
        $company = Company::factory()->has(Call::factory()->count(2))->create();

        $this->assertCount(2, $company->calls);
    }

    public function test_company_has_notes()
    {
        $company = Company::factory()->has(Note::factory()->count(2))->create();

        $this->assertCount(2, $company->notes);
    }

    public function test_company_has_contacts()
    {
        $company = Company::factory()->has(Contact::factory()->count(2))->create();

        $this->assertCount(2, $company->contacts);
    }

    public function test_company_has_activities()
    {
        $company = Company::factory()->has(Activity::factory()->count(2))->create();

        $this->assertCount(2, $company->activities);
    }
}
