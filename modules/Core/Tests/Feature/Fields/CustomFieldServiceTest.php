<?php
 

namespace Modules\Core\Tests\Feature\Fields;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Mockery\MockInterface;
use Modules\Contacts\App\Models\Contact;
use Modules\Core\Tests\Concerns\TestsCustomFields;
use Tests\TestCase;

class CustomFieldServiceTest extends TestCase
{
    use TestsCustomFields;

    public function test_it_tries_to_create_column_on_field_creation()
    {
        Schema::shouldReceive('whenTableDoesntHaveColumn')->once();

        $this->createNewField('Text');
    }

    public function test_it_adds_unique_index_on_unique_custom_fields()
    {
        Schema::shouldReceive('whenTableDoesntHaveColumn')
            ->once()
            ->andReturnUsing(function ($tableName, $columnName, $callback) {
                $mock = $this->mock(Blueprint::class, function (MockInterface $mock) {
                    $mock->shouldReceive('string')
                        ->with('field_id')
                        ->once()
                        ->andReturnSelf()
                        ->getMock()
                        ->shouldReceive('nullable')
                        ->withNoArgs()
                        ->once()
                        ->andReturnSelf()
                        ->getMock()
                        ->shouldReceive('unique')
                        ->with('field_id', 'field_id_unique_index');
                });

                $callback($mock);
            });

        $this->createNewField('Text', ['is_unique' => true]);
    }

    public function test_it_drops_unique_index_when_unmarking_as_unique()
    {
        Schema::shouldReceive('table')
            ->once()
            ->andReturnUsing(function ($tableName, $callback) {
                $mock = $this->mock(Blueprint::class, function (MockInterface $blueprintMock) {
                    $blueprintMock->shouldReceive('dropUnique')
                        ->with('field_id_unique_index')
                        ->once()
                        ->andReturnSelf();
                });

                $callback($mock);
            });

        $field = $this->createNewField('Text', ['is_unique' => true]);

        $this->service->update(['is_unique' => false], $field);
    }

    public function test_it_drops_foreign_keys_when_field_is_deleted()
    {
        $field = $this->createNewField('Radio');

        Schema::shouldReceive('whenTableHasColumn')
            ->once()
            ->with($this->customFieldsResource, 'field_id', Mockery::on(fn () => true))
            ->andReturnUsing(function ($table, $column, $callback) {
                $blueprintMock = $this->mock(Blueprint::class);
                $blueprintMock->shouldReceive('dropForeign')
                    ->once()
                    ->with('field_id_foreign_key');

                $blueprintMock->shouldReceive('dropColumn')
                    ->once()
                    ->with('field_id');

                $callback($blueprintMock);
            });

        Schema::shouldReceive('getForeignKeysForColumn')
            ->once()
            ->with($this->customFieldsResource, 'field_id')
            ->andReturn([['name' => 'field_id_foreign_key']]);

        $this->service->delete($field);
    }

    public function test_it_drops_column_when_field_is_deleted()
    {
        $field = $this->createNewField('Text');

        Schema::shouldReceive('whenTableHasColumn')
            ->once()
            ->with($this->customFieldsResource, 'field_id', Mockery::on(fn () => true))
            ->andReturnUsing(function ($table, $column, $callback) {
                $blueprintMock = $this->partialMock(Blueprint::class);
                $blueprintMock->shouldReceive('dropColumn')
                    ->once()
                    ->with('field_id');

                $callback($blueprintMock);
            });

        $this->service->delete($field);
    }

    public function test_it_set_constraint_to_null_when_field_option_is_deleted()
    {
        $field = $this->findField('cf_custom_field_radio');

        $contact = Contact::factory()->create(['cf_custom_field_radio' => $field->options->first()->getKey()]);

        $this->service->update(['options' => [['name' => 'option 2']]], $field);

        $this->assertNull($contact->fresh()->cf_custom_field_radio);
    }
}
