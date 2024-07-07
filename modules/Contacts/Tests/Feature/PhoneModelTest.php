<?php
 

namespace Modules\Contacts\Tests\Feature;

use Modules\Contacts\App\Models\Contact;
use Modules\Contacts\App\Models\Phone;
use Modules\Core\Database\Seeders\CountriesSeeder;
use Tests\TestCase;

class PhoneModelTest extends TestCase
{
    public function test_it_serializes_the_type_name()
    {
        $this->seed(CountriesSeeder::class);
        $contact = Contact::factory()->has(Phone::factory(), 'phones')->create();

        $this->assertArrayHasKey('type', $contact->phones[0]->toArray());
        $this->assertSame($contact->phones[0]->type->name, $contact->phones[0]->toArray()['type']);
    }
}
