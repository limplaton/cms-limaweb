<?php
 

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\App\Models\Import;
use Modules\Users\App\Models\User;

class ImportFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Import::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'file_path' => 'fake/path/file.csv',
            'resource_name' => 'contacts',
            'status' => 'mapping',
            'imported' => 0,
            'skipped' => 0,
            'duplicates' => 0,
            'user_id' => User::factory(),
            'completed_at' => null,
            'data' => ['mappings' => []],
        ];
    }
}
