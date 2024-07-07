<?php
 

namespace Modules\Core\Tests\Concerns;

use Illuminate\Support\Facades\Schema;
use Modules\Core\App\Facades\Fields;
use Modules\Core\App\Fields\CustomFieldService;
use Modules\Core\App\Models\CustomField;

trait TestsCustomFields
{
    protected string $customFieldsResource = 'contacts';

    protected CustomFieldService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::spy();

        $this->service = new CustomFieldService;
    }

    protected function fieldsTypesThatRequiresDatabaseColumnCreation()
    {
        return Fields::customFieldable()->where('multioptionable', false)->keys()->all();
    }

    protected function fieldsTypesThatDoesntRequiresDatabaseColumnCreation()
    {
        return Fields::customFieldable()->where('multioptionable', true)->keys()->all();
    }

    protected function countAllFields(): int
    {
        return CustomField::count();
    }

    protected function findField(string $fieldId): CustomField
    {
        return CustomField::where(
            'resource_name',
            $this->customFieldsResource
        )->where('field_id', $fieldId)->first();
    }

    protected function createNewField(string $type, array $data = [])
    {
        return $this->service->create(array_merge([
            'field_type' => $type,
            'field_id' => 'field_id',
            'label' => 'Label',
            'resource_name' => $this->customFieldsResource,
            'options' => [],
        ], $data));
    }
}
