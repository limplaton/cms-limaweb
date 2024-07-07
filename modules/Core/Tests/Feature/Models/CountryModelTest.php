<?php
 

namespace Modules\Core\Tests\Feature\Models;

use Illuminate\Support\Facades\Lang;
use Modules\Core\App\Models\Country;
use Modules\Core\Database\Seeders\CountriesSeeder;
use Tests\TestCase;

class CountryModelTest extends TestCase
{
    public function test_country_can_be_translated_with_custom_group()
    {
        $this->seed(CountriesSeeder::class);
        $model = Country::first();

        Lang::addLines(['custom.country.'.$model->id => 'Changed'], 'en');

        $this->assertSame('Changed', $model->name);
    }

    public function test_country_can_be_translated_with_lang_key()
    {
        $this->seed(CountriesSeeder::class);
        $model = Country::first()->forceFill(['name' => 'custom.country.some']);

        Lang::addLines(['custom.country.some' => 'Changed'], 'en');

        $this->assertSame('Changed', $model->name);
    }

    public function test_it_uses_database_name_when_no_custom_trans_available()
    {
        $this->seed(CountriesSeeder::class);
        $model = Country::first()->forceFill(['name' => 'Database Name']);

        $this->assertSame('Database Name', $model->name);
    }

    public function test_it_can_retrieve_a_list_of_countries()
    {
        $this->assertIsArray(Country::list());
        $this->assertNotEmpty(Country::list());
        $this->assertCount(249, Country::list());
    }
}
