<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\App\Fields\CustomFieldFileCache;
use Modules\Core\App\Fields\CustomFieldService;
use Modules\Core\App\Models\CustomField;
use Modules\Core\App\Models\CustomFieldOption;

class CustomFieldsSeeder extends Seeder
{
    /**
     * Custom fields resource.
     */
    public string $resourceName = 'contacts';

    /**
     * Change the resource na me the custom fields should be seeded for.
     */
    public function forResource(string $resourceName): static
    {
        $this->resourceName = $resourceName;

        return $this;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Do not flush the fields cache dozens of time one after another
        // when running unit tests, will flush the cache once all fields are seeded.
        CustomField::withoutEvents(function () {
            CustomFieldOption::withoutEvents(function () {
                $service = new CustomFieldService();

                $service->create([
                    'resource_name' => $this->resourceName,
                    'field_id' => 'cf_custom_field_checkbox',
                    'field_type' => 'Checkbox',
                    'label' => 'Checkbox',
                    'options' => [['name' => 'Checkbox 1', 'display_order' => 1], ['name' => 'Checkbox 2', 'display_order' => 2]],
                ]);

                $service->create([
                    'resource_name' => $this->resourceName,
                    'field_id' => 'cf_custom_field_boolean',
                    'field_type' => 'Boolean',
                    'label' => 'Boolean',
                ]);

                $service->create([
                    'resource_name' => $this->resourceName,
                    'field_id' => 'cf_custom_field_date',
                    'field_type' => 'Date',
                    'label' => 'Date',
                ]);

                $service->create([
                    'resource_name' => $this->resourceName,
                    'field_id' => 'cf_custom_field_datetime',
                    'field_type' => 'DateTime',
                    'label' => 'DateTime',
                ]);

                $service->create([
                    'resource_name' => $this->resourceName,
                    'field_id' => 'cf_custom_field_work_email',
                    'field_type' => 'Email',
                    'label' => 'Work Email',
                ]);

                $service->create([
                    'resource_name' => $this->resourceName,
                    'field_id' => 'cf_custom_field_multiselect',
                    'field_type' => 'MultiSelect',
                    'label' => 'MultiSelect',
                    'options' => [['name' => 'MultiSelect 1', 'display_order' => 1], ['name' => 'MultiSelect 2', 'display_order' => 2]],
                ]);

                $service->create([
                    'resource_name' => $this->resourceName,
                    'field_id' => 'cf_custom_field_number',
                    'field_type' => 'Number',
                    'label' => 'Number',
                ]);

                $service->create([
                    'resource_name' => $this->resourceName,
                    'field_id' => 'cf_custom_field_numeric',
                    'field_type' => 'Numeric',
                    'label' => 'Numeric',
                ]);

                $service->create([
                    'resource_name' => $this->resourceName,
                    'field_id' => 'cf_custom_field_radio',
                    'field_type' => 'Radio',
                    'label' => 'Radio',
                    'options' => [['name' => 'Radio 1', 'display_order' => 1], ['name' => 'Radio 2', 'display_order' => 2]],
                ]);

                $service->create([
                    'resource_name' => $this->resourceName,
                    'field_id' => 'cf_custom_field_select',
                    'field_type' => 'Select',
                    'label' => 'Select',
                    'options' => [['name' => 'Select 1', 'display_order' => 1], ['name' => 'Select 2', 'display_order' => 2]],
                ]);

                $service->create([
                    'resource_name' => $this->resourceName,
                    'field_id' => 'cf_custom_field_text',
                    'field_type' => 'Text',
                    'label' => 'Text',
                ]);

                $service->create([
                    'resource_name' => $this->resourceName,
                    'field_id' => 'cf_custom_field_textarea',
                    'field_type' => 'Textarea',
                    'label' => 'Textarea',
                ]);

                $service->create([
                    'resource_name' => $this->resourceName,
                    'field_id' => 'cf_custom_field_timezone',
                    'field_type' => 'Timezone',
                    'label' => 'Timezone',
                ]);

                $service->create([
                    'resource_name' => $this->resourceName,
                    'field_id' => 'cf_custom_field_url',
                    'field_type' => 'Url',
                    'label' => 'Url',
                ]);

                $service->create([
                    'resource_name' => $this->resourceName,
                    'field_id' => 'cf_custom_field_color_swatch',
                    'field_type' => 'ColorSwatch',
                    'label' => 'Custom Color',
                ]);
            });
        });

        CustomFieldFileCache::flush();
    }
}
