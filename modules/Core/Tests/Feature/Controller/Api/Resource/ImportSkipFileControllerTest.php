<?php
 

namespace Modules\Core\Tests\Feature\Controller\Api\Resource;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Core\App\Models\Import;
use Tests\TestCase;

class ImportSkipFileControllerTest extends TestCase
{
    public function test_a_skip_file_can_be_downloaded()
    {
        Storage::fake('local');
        $this->signIn();
        $import = $this->fakeImportWithSkipFile();

        $this->getJson('/api/contacts/import/'.$import->id.'/skip-file')->assertDownload($import->skip_file_filename);
    }

    public function test_cannot_download_skip_file_if_not_import_creator()
    {
        Storage::fake('local');
        $this->signIn();
        $import = $this->fakeImportWithSkipFile();

        $this->asRegularUser()->signIn();

        $this->getJson('/api/contacts/import/'.$import->id.'/skip-file')->assertForbidden();
    }

    public function test_user_can_upload_only_csv_skip_file()
    {
        Storage::fake('local');
        $this->signIn();
        $import = $this->fakeImportWithSkipFile();

        $this->postJson('/api/contacts/import/'.$import->id.'/skip-file', [
            'skip_file' => UploadedFile::fake()->image('photo.jpg'),
        ])->assertJsonValidationErrors(['skip_file']);
    }

    public function test_skip_file_can_be_reuploaded()
    {
        Storage::fake('local');

        $this->signIn();
        $import = $this->fakeImportWithSkipFile();

        $this->postJson('/api/contacts/import/'.$import->id.'/skip-file', [
            'skip_file' => $this->createFakeImportFile(),
        ])->assertOk()->assertJson([
            'status' => 'mapping',
        ]);
        $import->refresh();
        Storage::has($import->file_path);
    }

    public function test_cannot_reupload_skip_file_if_not_import_creator()
    {
        Storage::fake('local');
        $this->signIn();
        $import = $this->fakeImportWithSkipFile();

        $this->asRegularUser()->signIn();

        $this->postJson('/api/contacts/import/'.$import->id.'/skip-file')->assertForbidden();
    }

    public function test_it_show_404_when_import_doesnt_have_skip_file()
    {
        Storage::fake('local');
        $this->signIn();
        $import = $this->createFakeImportedImportInstance();

        $this->getJson('/api/contacts/import/'.$import->id.'/skip-file')->assertNotFound();
        $this->postJson('/api/contacts/import/'.$import->id.'/skip-file')->assertNotFound();
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

    protected function fakeImportWithSkipFile()
    {
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

        return $import;
    }
}
