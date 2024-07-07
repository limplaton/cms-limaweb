<?php
 

namespace Modules\Core\Tests\Feature\Models;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Modules\Contacts\App\Models\Contact;
use Modules\Core\App\Fields\Text;
use Modules\Core\App\Models\CustomField;
use Tests\TestCase;

class CustomFieldTest extends TestCase
{
    public function test_custom_field_has_options()
    {
        $field = $this->makeField(['field_type' => 'Checkbox']);
        $field->save();

        $field->options()->createMany([
            ['name' => 'Option 1', 'display_order' => 1],
            ['name' => 'Option 2', 'display_order' => 2],
        ]);

        $this->assertCount(2, $field->options);
    }

    public function test_custom_field_options_are_sorted_properly()
    {
        $field = $this->makeField(['field_type' => 'Checkbox']);
        $field->save();

        $field->options()->createMany([
            ['name' => 'Option 1', 'display_order' => 2],
            ['name' => 'Option 2', 'display_order' => 1],
        ]);

        $this->assertSame('Option 2', $field->options[0]->name);
        $this->assertSame('Option 1', $field->options[1]->name);
    }

    public function test_custom_field_has_relation_name()
    {
        $field = $this->makeField(['field_type' => 'Checkbox']);

        $this->assertEquals('customField'.Str::studly($field->field_id), $field->relationName);
        $this->assertEquals('customField'.Str::studly($field->field_id), $field->relationName);
    }

    public function test_custom_field_has_field_instance()
    {
        $field = $this->makeField(['field_type' => 'Text']);

        $this->assertInstanceOf(Text::class, $field->instance());
    }

    public function test_can_determine_whether_custom_field_is_multi_optionable()
    {
        $field = $this->makeField(['field_type' => 'Checkbox']);

        $this->assertTrue($field->isMultiOptionable());

        $field = $this->makeField(['field_type' => 'Text']);

        $this->assertFalse($field->isMultiOptionable());
    }

    public function test_can_determine_whether_custom_field_optionable()
    {
        $field = $this->makeField(['field_type' => 'Radio']);

        $this->assertTrue($field->isOptionable());

        $field = $this->makeField(['field_type' => 'Text']);

        $this->assertFalse($field->isOptionable());
    }

    public function test_custom_field_options_are_prepared_properly()
    {
        $field = $this->makeField();
        $field->save();

        $field->options()->createMany([
            ['name' => 'Option 1', 'display_order' => 1, 'swatch_color' => '#333333'],
            ['name' => 'Option 2', 'display_order' => 2, 'swatch_color' => '#333332'],
        ]);
        $prepared = $field->prepareOptions();

        $this->assertEquals([
            'id' => $field->options[0]->id,
            'name' => 'Option 1',
            'swatch_color' => '#333333',
        ], $prepared[0]);

        $this->assertEquals([
            'id' => $field->options[1]->id,
            'name' => 'Option 2',
            'swatch_color' => '#333332',
        ], $prepared[1]);
    }

    public function test_custom_field_related_options_are_prepared_properly()
    {
        $field = $this->makeField(['resource_name' => 'contacts', 'field_type' => 'Checkbox']);

        $field->save();

        $field->options()->createMany([
            ['name' => 'Option 1', 'swatch_color' => '#333333', 'display_order' => 1],
            ['name' => 'Option 2', 'display_order' => 2],
        ]);

        $related = Contact::factory()->create();

        $related->{$field->relationName}()->attach([$field->options[0]->id => ['custom_field_id' => $field->id]]);
        $prepared = $field->prepareRelatedOptions($related);

        $this->assertEquals([
            'id' => $field->options[0]->id,
            'name' => 'Option 1',
            'swatch_color' => '#333333',
        ], $prepared[0]);
    }

    public function test_custom_field_can_be_translated_with_custom_group()
    {
        $model = $this->makeField(['label' => 'Original']);
        $model->save();

        Lang::addLines(['custom.custom_field.'.$model->field_id => 'Changed'], 'en');

        $this->assertSame('Changed', $model->label);
    }

    public function test_custom_field_can_be_translated_with_lang_key()
    {
        $model = $this->makeField(['label' => 'custom.custom_field.some']);
        $model->save();

        Lang::addLines(['custom.custom_field.some' => 'Changed'], 'en');

        $this->assertSame('Changed', $model->label);
    }

    public function test_it_uses_database_name_when_no_custom_trans_available()
    {
        $model = $this->makeField(['label' => 'Database Label']);
        $model->save();

        $this->assertSame('Database Label', $model->label);
    }

    protected function makeField($attrs = [])
    {
        return new CustomField(array_merge([
            'field_id' => 'cf_field_id',
            'field_type' => 'Text',
            'resource_name' => 'resource',
            'label' => 'Label',
        ], $attrs));
    }
}
