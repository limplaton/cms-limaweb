<?php
 

namespace Modules\Core\Tests\Feature\Controller\Api\Resource;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Modules\Core\App\Models\Import;
use Modules\Core\App\Resource\Import\Import as ResourceImport;
use Modules\Core\Database\Seeders\CountriesSeeder;
use Modules\Users\App\Models\User;
use Tests\TestCase;

class ImportControllerTest extends TestCase
{
    public function test_unauthenticated_user_cannot_access_import_endpoints()
    {
        $this->getJson('/api/contacts/import')->assertUnauthorized();
        $this->postJson('/api/contacts/import/upload')->assertUnauthorized();
        $this->postJson('/api/contacts/import/FAKE_ID')->assertUnauthorized();
        $this->deleteJson('/api/contacts/import/FAKE_ID')->assertUnauthorized();
        $this->getJson('/api/contacts/import/sample')->assertUnauthorized();
    }

    public function test_user_can_retrieve_all_imports()
    {
        $user = $this->signIn();

        Import::factory()->count(2)->create([
            'file_path' => 'fake/path/file.csv',
            'resource_name' => 'contacts',
            'status' => 'mapping',
            'imported' => 0,
            'skipped' => 0,
            'duplicates' => 0,
            'user_id' => $user->id,
        ]);

        $this->getJson('/api/contacts/import')
            ->assertJsonCount(2)
            ->assertJson([
                [
                    'file_name' => 'file.csv',
                    'resource_name' => 'contacts',
                    'status' => 'mapping',
                    'imported' => 0,
                    'skipped' => 0,
                    'duplicates' => 0,
                    'user_id' => $user->id,
                ],
            ]);
    }

    public function test_non_super_admin_can_retrieve_only_own_imports()
    {
        $user = $this->asRegularUser()->signIn();

        Import::factory()->for(User::factory())->create();
        $import = Import::factory()->create(['user_id' => $user->id]);

        $this->getJson('/api/contacts/import')
            ->assertJsonCount(1)
            ->assertJson([
                [
                    'id' => $import->id,
                ],
            ]);
    }

    public function test_user_can_upload_import_file()
    {
        $this->signIn();

        Storage::fake('local');

        $this->postJson('/api/contacts/import/upload', [
            'file' => $this->createFakeImportFile(),
        ])->assertJson([
            'file_name' => 'test.csv',
            'resource_name' => 'contacts',
            'status' => 'mapping',
            'mappings' => [
                [
                    'original' => 'First Name',
                    'detected_attribute' => 'first_name',
                    'attribute' => 'first_name',
                    'preview' => 'John, Jane',
                    'skip' => false,
                    'auto_detected' => true,
                ],
                [
                    'original' => 'E-Mail Address',
                    'detected_attribute' => 'email',
                    'attribute' => 'email',
                    'preview' => 'john@example.com, jane@example.com',
                    'skip' => false,
                    'auto_detected' => true,
                ],
                [
                    'original' => 'NonExistent Field',
                    'detected_attribute' => null,
                    'attribute' => null,
                    'preview' => '',
                    'skip' => true,
                    'auto_detected' => false,
                ],
            ],
        ])->assertJsonStructure(['fields']);

        $import = Import::first();

        Storage::assertExists($import->file_path);
    }

    public function test_it_updates_the_import_mappings_before_importing_the_data()
    {
        $this->signIn();

        Storage::fake('local');

        $this->postJson('/api/contacts/import/upload', [
            'file' => $this->createFakeImportFile(),
        ]);

        $import = Import::first();

        $this->postJson("/api/contacts/import/{$import->id}", [
            'mappings' => $mappings = [
                [
                    'original' => 'First Name',
                    'detected_attribute' => 'first_name',
                    'attribute' => 'last_name',
                    'preview' => 'John, Jane',
                    'skip' => false,
                    'auto_detected' => true,
                ],
                [
                    'original' => 'E-Mail Address',
                    'detected_attribute' => 'email',
                    'attribute' => 'first_name',
                    'preview' => 'john@example.com, jane@example.com',
                    'skip' => false,
                    'auto_detected' => true,
                ],
                [
                    'original' => 'NonExistent Field',
                    'detected_attribute' => null,
                    'attribute' => 'email',
                    'preview' => '',
                    'skip' => true,
                    'auto_detected' => false,
                ],
            ],
        ]);

        $this->assertEquals($mappings, $import->fresh()->data['mappings']);
    }

    public function test_non_super_admin_user_can_perform_import_only_on_own_import()
    {
        $this->asRegularUser()->signIn();
        $import = Import::factory()->create();

        Storage::fake('local');

        $this->postJson("/api/contacts/import/{$import->id}", [
            'mappings' => [
                [
                    'original' => 'First Name',
                    'detected_attribute' => 'first_name',
                    'attribute' => 'last_name',
                    'preview' => 'John, Jane',
                    'skip' => false,
                    'auto_detected' => true,
                ],
            ],
        ])->assertForbidden();
    }

    public function test_import_requires_mappings()
    {
        $this->signIn();

        $import = Import::factory()->create();

        $this->postJson("/api/contacts/import/{$import->id}", [
            'mappings' => [],
        ])->assertJsonValidationErrors('mappings');
    }

    public function test_import_requires_mappings_auto_detected_attribute()
    {
        $this->signIn();

        $import = Import::factory()->create();

        $this->postJson("/api/contacts/import/{$import->id}", [
            'mappings' => [
                [
                    'original' => 'E-Mail Address',
                    'detected_attribute' => 'email',
                    'attribute' => 'email',
                    'skip' => false,
                ],
            ],
        ])->assertJsonValidationErrorFor('mappings.0.auto_detected');
    }

    public function test_import_does_not_requires_mapping_if_has_next_batch()
    {
        $this->signIn();

        $import = Import::factory()->create(['data' => ['next_batch' => 2]]);

        $this->postJson("/api/contacts/import/{$import->id}", [
            'mappings' => [
                [
                    'original' => 'E-Mail Address',
                    'detected_attribute' => 'email',
                    'attribute' => 'email',
                    'skip' => false,
                ],
            ],
        ])->assertJsonMissingValidationErrors('mappings');
    }

    public function test_it_properly_calculates_batches()
    {
        $this->signIn();

        Storage::fake('local');

        $originalLimit = ResourceImport::getDefaultLimit();
        ResourceImport::setDefaultLimit(13);

        $import = $this->createFakeImportedImportInstance(35);

        ResourceImport::setDefaultLimit($originalLimit);

        $this->assertEquals(1, $import->totalBatchesProcessed());
        $this->assertEquals(3, $import->totalBatches());
        $this->assertEquals(2, $import->nextBatch());
    }

    public function test_import_can_be_reverted()
    {
        $this->signIn();

        $import = $this->createFakeImportedImportInstance();

        $this->assertDatabaseCount('contacts', 2);

        $this->deleteJson("/api/contacts/import/{$import->id}/revert?limit=1");
        $this->deleteJson("/api/contacts/import/{$import->id}/revert?limit=1");

        $this->assertDatabaseEmpty('contacts');
    }

    public function test_unauthorized_user_cannot_revert_import()
    {
        $this->asRegularUser()->signIn();

        $import = $this->createFakeImportedImportInstance();
        $import->forceFill(['user_id' => $this->createUser()->id])->save();

        $this->deleteJson("/api/contacts/import/{$import->id}/revert?limit=1")->assertForbidden();
    }

    public function test_old_imports_cannot_be_reverted()
    {
        $this->signIn();

        Carbon::setTestNow(now()->subHours(config('core.import.revertable_hours') + 1));

        $import = $this->createFakeImportedImportInstance();

        Carbon::setTestNow(null);
        $this->deleteJson("/api/contacts/import/{$import->id}/revert?limit=500")->assertNotFound();

        $this->assertDatabaseCount('contacts', 2);
    }

    public function test_import_requires_mappings_original_attribute()
    {
        $this->signIn();

        $import = Import::factory()->create();

        $this->postJson("/api/contacts/import/{$import->id}", [
            'mappings' => [
                [
                    'detected_attribute' => 'email',
                    'attribute' => 'email',
                    'skip' => false,
                    'auto_detected' => true,
                ],
            ],
        ])->assertJsonValidationErrorFor('mappings.0.original');
    }

    public function test_import_requires_mappings_skip_attribute()
    {
        $this->signIn();

        $import = Import::factory()->create();

        $this->postJson("/api/contacts/import/{$import->id}", [
            'mappings' => [
                [
                    'original' => 'E-Mail Address',
                    'detected_attribute' => 'email',
                    'attribute' => 'email',
                    'auto_detected' => true,
                ],
            ],
        ])->assertJsonValidationErrorFor('mappings.0.skip');
    }

    public function test_import_requires_detected_attribute_to_be_present_in_the_mappings()
    {
        $this->signIn();

        $import = Import::factory()->create();

        $this->postJson("/api/contacts/import/{$import->id}", [
            'mappings' => [
                [
                    'original' => 'E-Mail Address',
                    'attribute' => 'email',
                    'skip' => false,
                    'auto_detected' => true,
                ],
            ],
        ])->assertJsonValidationErrorFor('mappings.0.detected_attribute');
    }

    public function test_it_fails_when_import_rows_exceedes_configured_max_rows()
    {
        config(['core.import.max_rows' => $maxRows = 20]);

        $this->signIn();

        Storage::fake('local');

        $this->postJson('/api/contacts/import/upload', [
            'file' => $this->createFakeImportFile($maxRows + 1),
        ]);

        $import = Import::first();

        $this->postJson("/api/contacts/import/{$import->id}", [
            'mappings' => $import->data['mappings'],
        ])->assertJson(['rows_exceeded' => true]);
    }

    public function test_user_can_upload_only_csv_file()
    {
        $this->signIn();

        $this->postJson('/api/contacts/import/upload', [
            'file' => UploadedFile::fake()->image('photo.jpg'),
        ])->assertJsonValidationErrors(['file']);
    }

    public function test_user_can_delete_import()
    {
        $this->signIn();

        $import = Import::factory()->create();

        $this->deleteJson("/api/contacts/import/{$import->id}")->assertNoContent();
    }

    public function test_unauthorized_user_cannot_delete_import()
    {
        $this->asRegularUser()->signIn();
        $user = User::factory()->create();

        $import = Import::factory()->for($user)->create();

        $this->deleteJson("/api/contacts/import/{$import->id}")->assertForbidden();
    }

    public function test_user_can_download_import_sample()
    {
        $this->seed(CountriesSeeder::class);

        $this->signIn();

        $this->getJson('/api/contacts/import/sample')->assertDownload('sample.csv');
    }

    public function test_it_generates_skip_file_when_validation_fails()
    {
        $this->signIn();

        Storage::fake('local');

        $file = UploadedFile::fake()->createWithContent(
            'test.csv',
            implode("\n", ['First Name,E-Mail Address', ...['John,invalid-email-address']])
        );

        $this->postJson('/api/contacts/import/upload', [
            'file' => $file,
        ])->assertOk();

        $import = Import::first();

        $this->postJson("/api/contacts/import/{$import->id}", [
            'mappings' => $import->data['mappings'],
        ])->assertOk();

        $import->refresh();

        $this->assertTrue($import->isUsingSkipFile());
        Storage::assertExists($import->skip_file_path);
    }

    protected function createFakeImportedImportInstance($rows = 2)
    {
        Storage::fake('local');

        $this->postJson('/api/contacts/import/upload', [
            'file' => $this->createFakeImportFile($rows),
        ])->assertOk();

        $import = Import::first();

        $response = $this->postJson("/api/contacts/import/{$import->id}", [
            'mappings' => $import->data['mappings'],
        ])->assertOk();

        return Import::find($response['id']);
    }

    protected function createFakeImportFile($totalRows = 2)
    {
        $header = 'First Name,E-Mail Address,NonExistent Field';
        $rows = [];

        for ($i = 0; $i < $totalRows; $i++) {
            $default = 'John,john@example.com';

            if ($i === 0) {
                $rows[] = $default;
            } elseif ($i === 1) {
                $rows[] = 'Jane,jane@example.com';
            } else {
                $rows[] = $default;
            }
        }

        $content = implode("\n", [$header, ...$rows]);

        return UploadedFile::fake()->createWithContent(
            'test.csv',
            $content
        );
    }
}
