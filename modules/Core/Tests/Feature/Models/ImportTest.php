<?php
 

namespace Modules\Core\Tests\Feature\Models;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Core\App\Models\Import;
use Modules\Users\App\Models\User;
use Tests\TestCase;

class ImportTest extends TestCase
{
    public function test_import_has_status()
    {
        $this->assertEquals('mapping', $this->makeImport(['status' => 'mapping'])->status);
        $this->assertEquals('mapping', $this->makeImport(['status' => 1])->status);

        $this->assertEquals('in-progress', $this->makeImport(['status' => 'in-progress'])->status);
        $this->assertEquals('in-progress', $this->makeImport(['status' => 2])->status);

        $this->assertEquals('finished', $this->makeImport(['status' => 'finished'])->status);
        $this->assertEquals('finished', $this->makeImport(['status' => 3])->status);
    }

    public function test_import_has_filename()
    {
        $this->assertEquals('test.csv', $this->makeImport(['file_path' => 'imports/test.csv'])->file_name);
    }

    public function test_import_has_disk()
    {
        $this->assertEquals('local', $this->makeImport()->disk());
    }

    public function test_import_has_user()
    {
        $user = $this->createUser();
        $import = $this->makeImport(['user_id' => $user->id]);
        $import->save();

        $this->assertInstanceOf(User::class, $import->user);
    }

    public function test_file_is_removed_after_import_delete()
    {
        Storage::fake();

        $disk = Storage::disk('local');

        $path = $disk->putFile('/imports/'.uniqid(), UploadedFile::fake()->create('text.csv'));

        $import = $this->makeImport(['user_id' => $this->createUser()->id, 'file_path' => $path]);
        $import->save();

        Storage::assertExists($path);
        $import->delete();
        Storage::assertMissing($path);
    }

    protected function makeImport($attrs = [])
    {
        return new Import(array_merge([
            'file_path' => 'imports/text.csv',
            'resource_name' => 'resource',
            'status' => 'mapping',
            'user_id' => 1,
            'data' => [],
        ], $attrs));
    }
}
