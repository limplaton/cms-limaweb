<?php

use Database\Seeders\CustomFieldsSeeder;
use Illuminate\Database\Migrations\Migration;
use Modules\Core\App\Contracts\Resources\AcceptsCustomFields;
use Modules\Core\App\Facades\Fields;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Fields\CustomFieldService;
use Modules\Core\App\Models\CustomField;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $seeder = new CustomFieldsSeeder;

        foreach ($this->resources() as $resource) {
            $seeder->forResource($resource->name())->run();
        }

        Fields::flushLoadedCache();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $service = new CustomFieldService;

        foreach ($this->resources() as $resource) {
            foreach (CustomField::where('resource_name', $resource->name())->get() as $field) {
                $service->delete($field);
            }
        }
    }

    /**
     * @return array<\Modules\Core\App\Resource\Resource>
     */
    protected function resources(): array
    {
        return Innoclapps::registeredResources()->whereInstanceOf(AcceptsCustomFields::class)->all();
    }
};
