<?php
 

namespace Modules\Core\Tests\Feature;

use Illuminate\Support\Carbon;
use Modules\Contacts\App\Models\Contact;
use Modules\Core\App\Facades\Fields;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Fields\DateTime;
use Modules\Core\App\Fields\FieldsCollection;
use Modules\Core\App\Fields\Text;
use Modules\Core\App\Resource\Exceptions\InvalidExportTypeException;
use Modules\Core\App\Resource\Export;
use Tests\TestCase;

class ResourceExportTest extends TestCase
{
    public function test_it_uses_resource_name_as_export_filename()
    {
        $export = $this->createExportInstance();

        $this->assertSame('contacts', $export->fileName());
    }

    public function test_export_has_headings()
    {
        Fields::replace('contacts', [
            Text::make('first_name', 'First name'),
            Text::make('last_name', 'Last name'),
        ]);

        $export = $this->createExportInstance();

        $this->assertEquals(['First name', 'Last name'], $export->headings());
    }

    public function test_it_adds_the_application_timezone_in_datetime_headings()
    {
        Fields::replace('contacts', [
            DateTime::make('created_at', 'Created At'),
        ]);

        $export = $this->createExportInstance();

        $this->assertStringContainsString(config('app.timezone'), $export->headings()[0]);
    }

    public function test_it_adds_copy_of_datetime_fields_in_local_timezone()
    {
        $user = $this->createUser();

        Fields::replace('contacts', [
            Text::make('first_name', 'First Name'),
            DateTime::make('created_at', 'Created At'),
        ]);

        $export = $this->createExportInstance()->setUser($user);
        $fields = $export->getFields();

        $this->assertCount(3, $fields);
        $this->assertInstanceOf(DateTime::class, $fields[2]);
        $this->assertTrue($fields[2]->meta()['export_local']);
    }

    public function test_it_can_set_export_fields()
    {
        Fields::replace('contacts', [
            DateTime::make('created_at', 'Created At'),
            $newField = Text::make('first_name', 'First Name'),
        ]);

        $fields = $this->createExportInstance()->setFields(new FieldsCollection([$newField]))->getFields();

        $this->assertCount(1, $fields);
        $this->assertInstanceOf(Text::class, $fields[0]);
    }

    public function test_it_adds_the_user_timezone_in_local_datetime_headings()
    {
        $user = $this->createUser();

        Fields::replace('contacts', [
            DateTime::make('created_at', 'Created At'),
        ]);

        $export = $this->createExportInstance()->setUser($user);

        $field = $export->getFields()[1];
        $this->assertStringContainsString($user->timezone, $export->heading($field));
    }

    public function test_copy_of_datetime_field_has_custom_value_resolver()
    {
        $user = $this->createUser();
        $contact = Contact::factory()->create();

        Fields::replace('contacts', [
            DateTime::make('created_at', 'Created At'),
        ]);

        $export = $this->createExportInstance()->setUser($user);
        $field = $export->getFields()[1];

        $this->assertIsCallable($field->exportCallback);

        $value = $field->resolveForExport($contact);

        $this->assertInstanceOf(Carbon::class, $value);
        $this->assertSame($value->timezone->getName(), $user->timezone);
    }

    public function test_copy_of_datetime_field_custom_resolver_can_resolve_string_datetime()
    {
        $user = $this->createUser();
        $contact = Contact::factory()->create();

        Fields::replace('contacts', [
            DateTime::make('dummy', 'Dummy'),
        ]);

        $export = $this->createExportInstance()->setUser($user);
        $contact->dummy = '2024-03-09 14:00:00';
        $field = $export->getFields()[1];

        $value = $field->resolveForExport($contact);

        $this->assertInstanceOf(Carbon::class, $value);
        $this->assertSame($value->timezone->getName(), $user->timezone);
        $this->assertSame(
            $value->format('Y-m-d H:i:00'),
            Carbon::parse($contact->dummy)->timezone($user->timezone)->format('Y-m-d H:i:00')
        );
    }

    public function test_it_can_specify_export_type()
    {
        $this->signIn();
        $export = $this->createExportInstance();

        $download = $export->download('xls');

        $this->assertSame('xls', $download->getFile()->getExtension());
    }

    public function test_it_uses_default_export_type_when_not_provided()
    {
        $this->signIn();
        $export = $this->createExportInstance();

        $this->assertSame(Export::DEFAULT_TYPE, $export->download()->getFile()->getExtension());
    }

    public function test_cannot_perform_export_with_invalid_type()
    {
        $this->expectException(InvalidExportTypeException::class);
        $export = $this->createExportInstance();

        $export->download('invalid');
    }

    public function test_it_excludes_fields_from_export()
    {
        Fields::replace('contacts', [
            Text::make('first_name', 'First name'),
            Text::make('last_name', 'Last name')->excludeFromExport(),
        ]);

        $export = $this->createExportInstance();

        $this->assertCount(1, $export->getFields());
    }

    public function test_it_creates_the_export_collection()
    {
        $this->signIn();

        Contact::factory()->count(2)->create();

        $export = $this->createExportInstance();

        $this->assertCount(2, $export->collection());
    }

    public function test_export_queries_are_properly_executed_in_chunks()
    {
        $this->signIn();

        Contact::factory()->count(2)->create();
        $defaultChunkSize = Export::$chunkSize;
        Export::$chunkSize = 1;

        $export = $this->createExportInstance();

        $this->assertCount(2, $export->collection());

        Export::$chunkSize = $defaultChunkSize;
    }

    protected function createExportInstance()
    {
        $resource = Innoclapps::resourceByName('contacts');

        return new Export($resource, $resource->newQuery());
    }
}
