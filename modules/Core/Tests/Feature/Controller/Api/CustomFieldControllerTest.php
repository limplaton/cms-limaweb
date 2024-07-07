<?php
 

namespace Modules\Core\Tests\Feature\Controller\Api;

use Modules\Core\App\Facades\Fields;
use Modules\Core\Tests\Concerns\TestsCustomFields;
use Tests\TestCase;

class CustomFieldControllerTest extends TestCase
{
    use TestsCustomFields;

    public function test_unauthenticated_user_cannot_access_custom_fields_endpoints()
    {
        $this->getJson('/api/custom-fields')->assertUnauthorized();
        $this->getJson('/api/custom-fields/FAKE_ID')->assertUnauthorized();
        $this->postJson('/api/custom-fields')->assertUnauthorized();
        $this->putJson('/api/custom-fields/FAKE_ID')->assertUnauthorized();
        $this->deleteJson('/api/custom-fields/FAKE_ID')->assertUnauthorized();
    }

    public function test_unauthorized_user_cannot_access_custom_fields_endpoints()
    {
        $this->asRegularUser()->signIn();

        $this->getJson('/api/custom-fields')->assertForbidden();
        $this->getJson('/api/custom-fields/FAKE_ID')->assertForbidden();
        $this->postJson('/api/custom-fields')->assertForbidden();
        $this->putJson('/api/custom-fields/FAKE_ID')->assertForbidden();
        $this->deleteJson('/api/custom-fields/FAKE_ID')->assertForbidden();
    }

    public function test_custom_fields_can_be_retrieved()
    {
        $this->signIn();

        $count = $this->countAllFields() + 1;

        $this->createNewField('Text', [
            'field_id' => 'cf_new_custom_field',
            'label' => 'Label',
        ]);

        $this->getJson('/api/custom-fields?per_page=1000')
            ->assertOk()
            ->assertJsonCount($count, 'data')
            ->assertJson([
                'data' => [
                    [
                        'field_type' => 'Text',
                        'resource_name' => $this->customFieldsResource,
                        'field_id' => 'cf_new_custom_field',
                        'label' => 'Label',
                        'is_unique' => null,
                    ],
                ],
            ]);
    }

    public function test_custom_field_requires_resource_name()
    {
        $this->signIn();

        $this->postJson('/api/custom-fields', ['resource_name' => ''])->assertJsonValidationErrors('resource_name');
        $this->putJson('/api/custom-fields/FAKE_ID', ['resource_name' => ''])->assertJsonValidationErrors('resource_name');
    }

    public function test_custom_field_requires_field_type_on_creating_only()
    {
        $this->signIn();

        $availableTypes = Fields::customFieldsTypes();

        $this->postJson('/api/custom-fields', ['field_type' => ''])->assertJsonValidationErrors('field_type');
        $this->putJson('/api/custom-fields/FAKE_ID', ['field_type' => $availableTypes[0]])
            ->assertJsonMissingValidationErrors('field_type');
    }

    public function test_custom_field_accepts_only_supported_types()
    {
        $this->signIn();

        $this->postJson('/api/custom-fields', ['field_type' => 'dummy-type'])
            ->assertJsonValidationErrors('field_type');
    }

    public function test_properly_validates_the_custom_field_id_attribute()
    {
        $this->signIn();

        // Required on create
        $this->postJson('/api/custom-fields', [
            'field_id' => '',
        ])->assertJsonValidationErrors('field_id');

        // Required only on create
        $this->putJson('/api/custom-fields/FAKE_ID', [
            'field_id' => 'some_id',
        ])->assertJsonMissingValidationErrors('field_id');

        // Cannot be named "associations"
        $this->postJson('/api/custom-fields', [
            'field_id' => 'associations',
        ])->assertJsonValidationErrors('field_id');

        // Only lowercase alpha characters (a-z) and underscore (_) are accepted.
        $this->postJson('/api/custom-fields', [
            'field_id' => 'NonASCII-2@',
            'resource_name' => $this->customFieldsResource,
        ])->assertJsonValidationErrors('field_id');

        // Cannot be named column from database
        $this->postJson('/api/custom-fields', [
            'field_id' => 'first_name',
            'resource_name' => $this->customFieldsResource,
        ])->assertJsonValidationErrors('field_id');

        // Max 64 characterts
        $this->postJson('/api/custom-fields', [
            'field_id' => str_repeat('T', 65),
            'resource_name' => $this->customFieldsResource,
        ])->assertJsonValidationErrors('field_id');

        // Cannot be named from other existent fields e.q. custom fields
        // Create a field first
        $this->postJson('/api/custom-fields', [
            'field_type' => 'Text',
            'field_id' => 'test-field-id',
            'label' => 'Label',
            'resource_name' => $this->customFieldsResource,
        ]);

        $this->postJson('/api/custom-fields', [
            'field_id' => 'test-field-id',
            'resource_name' => $this->customFieldsResource,
        ])->assertJsonValidationErrors('field_id');
    }

    public function test_custom_field_requires_label()
    {
        $this->signIn();

        $this->postJson('/api/custom-fields', ['label' => ''])->assertJsonValidationErrors('label');
        $this->putJson('/api/custom-fields/FAKE_ID', ['label' => ''])->assertJsonValidationErrors('label');
    }

    public function test_optionable_custom_field_requires_options()
    {
        $this->signIn();

        $this->postJson('/api/custom-fields', [
            'field_type' => Fields::getOptionableCustomFieldsTypes()[0],
            'field_id' => 'some_id',
            'label' => 'Label',
            'resource_name' => $this->customFieldsResource,
            'options' => [],
        ])->assertJsonValidationErrors('options');
    }

    public function test_user_can_create_custom_fields()
    {
        $this->signIn();

        foreach (Fields::getNonOptionableCustomFieldsTypes() as $type) {
            $this->postJson('/api/custom-fields', [
                'field_type' => $type,
                'field_id' => $id = 'cf_some_id_'.strtolower($type),
                'label' => $type,
                'resource_name' => $this->customFieldsResource,
            ])->assertJson([
                'field_type' => $type,
                'field_id' => $id,
                'label' => $type,
                'resource_name' => $this->customFieldsResource,
            ]);
        }

        foreach (Fields::getOptionableCustomFieldsTypes() as $type) {
            $this->postJson('/api/custom-fields', [
                'field_type' => $type,
                'field_id' => $id = 'cf_some_id_'.strtolower($type),
                'label' => $type,
                'resource_name' => $this->customFieldsResource,
                'options' => $options = [['name' => 'Option 1'], ['name' => 'Option 2']],
            ])->assertJson([
                'field_type' => $type,
                'field_id' => $id,
                'label' => $type,
                'resource_name' => $this->customFieldsResource,
                'options' => $options,
            ]);
        }
    }

    public function test_user_can_update_custom_fields()
    {
        $this->signIn();

        $field = $this->createNewField('Checkbox', [
            'field_id' => 'cf_some_id_for_type',
            'label' => 'Label',
            'resource_name' => $this->customFieldsResource,
            'options' => [['name' => 'Option 1'], ['name' => 'Option 2']],
        ]);

        $option1 = $field->options->first(function ($option) {
            return $option->name === 'Option 1';
        });

        $this->putJson('/api/custom-fields/'.$field->id, [
            'label' => 'Changed Label',
            'options' => [['name' => 'New Option'], ['id' => $option1->id, 'name' => 'Option 1 Updated']],
            'resource_name' => $this->customFieldsResource,
        ])->assertJson([
            'label' => 'Changed Label',
        ]);

        $field->load('options');

        // Should be deleted
        $this->assertNull($field->options->first(function ($option) {
            return $option->name === 'Option 2';
        }));

        // Should be created
        $this->assertNotNull($field->options->first(function ($option) {
            return $option->name === 'New Option';
        }));

        // Should be updated
        $this->assertNotNull($option = $field->options->first(function ($option) {
            return $option->name === 'Option 1 Updated';
        }));

        $this->assertEquals($option->id, $option1->id);
    }

    public function test_field_type_is_not_updated_when_provided_on_update()
    {
        $this->signIn();

        $field = $this->createNewField('Text');

        $this->putJson('/api/custom-fields/'.$field->id, [
            'label' => 'Changed Label',
            'resource_name' => $this->customFieldsResource,
            'field_type' => 'Select',
        ])->assertJson([
            'field_type' => 'Text',
        ]);
    }

    public function test_custom_field_can_be_deleted()
    {
        $this->signIn();

        $field = $this->createNewField('Text');

        $this->deleteJson('/api/custom-fields/'.$field->id)->assertNoContent();
        $this->assertDatabaseMissing('contacts', ['id' => $field->id]);
    }

    public function test_field_id_is_not_updated_when_provided_on_update()
    {
        $this->signIn();

        $field = $this->createNewField('Text', ['field_id' => 'field_id']);

        $this->putJson('/api/custom-fields/'.$field->id, [
            'label' => 'Changed Label',
            'resource_name' => $this->customFieldsResource,
            'field_id' => 'changed_id',
        ])->assertJson([
            'field_id' => 'field_id',
        ]);
    }
}
