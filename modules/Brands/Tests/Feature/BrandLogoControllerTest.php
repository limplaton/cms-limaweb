<?php
 

namespace Modules\Brands\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Brands\App\Models\Brand;
use Tests\TestCase;

class BrandLogoControllerTest extends TestCase
{
    public function test_it_uploads_brand_view_logo()
    {
        $this->signIn();
        Storage::fake('public');

        $logo = UploadedFile::fake()->image('logo.jpg');
        $hasName = $logo->hashName();

        $brand = Brand::factory()->create();

        $this->postJson("/api/brands/$brand->id/logo/view", [
            'logo_view' => $logo,
        ])->assertExactJson([
            'path' => "brands/$hasName",
            'url' => $brand->fresh()->logoViewUrl,
        ]);

        Storage::disk('public')->assertExists("brands/$hasName");
    }

    public function test_brand_logo_view_can_be_deleted()
    {
        $this->signIn();
        Storage::fake('public');

        $logo = UploadedFile::fake()->image('logo.jpg');
        $brand = Brand::factory()->create();
        $hasName = $logo->hashName();

        $this->postJson("/api/brands/$brand->id/logo/view", [
            'logo_view' => $logo,
        ]);
        $this->deleteJson("/api/brands/$brand->id/logo/view")->assertOk();

        $this->assertNull($brand->fresh()->logo_view);
        Storage::disk('public')->assertMissing("brands/$hasName");
    }

    public function test_it_uploads_brand_mail_logo()
    {
        $this->signIn();
        Storage::fake('public');

        $logo = UploadedFile::fake()->image('logo.jpg');
        $hasName = $logo->hashName();

        $brand = Brand::factory()->create();

        $this->postJson("/api/brands/$brand->id/logo/mail", [
            'logo_mail' => $logo,
        ])->assertExactJson([
            'path' => "brands/$hasName",
            'url' => $brand->fresh()->logoMailUrl,
        ]);

        Storage::disk('public')->assertExists("brands/$hasName");
    }

    public function test_brand_logo_mail_can_be_deleted()
    {
        $this->signIn();
        Storage::fake('public');

        $logo = UploadedFile::fake()->image('logo.jpg');
        $brand = Brand::factory()->create();
        $hasName = $logo->hashName();

        $this->postJson("/api/brands/$brand->id/logo/mail", [
            'logo_mail' => $logo,
        ]);
        $this->deleteJson("/api/brands/$brand->id/logo/mail")->assertOk();

        $this->assertNull($brand->fresh()->logo_mail);
        Storage::disk('public')->assertMissing("brands/$hasName");
    }

    public function test_it_deletes_old_logo_when_reuploading()
    {
        $this->signIn();
        Storage::fake('public');

        $oldLogo = UploadedFile::fake()->image('logo.jpg');
        $brand = Brand::factory()->create();
        $oldHasName = $oldLogo->hashName();

        $this->postJson("/api/brands/$brand->id/logo/mail", [
            'logo_mail' => $oldLogo,
        ]);

        $logo = UploadedFile::fake()->image('logo.jpg');
        $this->postJson("/api/brands/$brand->id/logo/mail", [
            'logo_mail' => $logo,
        ]);

        Storage::disk('public')->assertMissing("brands/$oldHasName");
    }

    public function test_regular_user_cannot_upload_brand_mail_logo()
    {
        $this->asRegularUser()->signIn();

        $brand = Brand::factory()->create();

        $this->postJson("/api/brands/$brand->id/logo/mail", [
            'logo_mail' => '',
        ])->assertForbidden();
    }

    public function test_regular_user_cannot_upload_brand_view_logo()
    {
        $this->asRegularUser()->signIn();

        $brand = Brand::factory()->create();

        $this->postJson("/api/brands/$brand->id/logo/view", [
            'logo_view' => '',
        ])->assertForbidden();
    }

    public function test_regular_user_cannot_delete_brand_view_logo()
    {
        $this->asRegularUser()->signIn();

        $brand = Brand::factory()->create();

        $this->deleteJson("/api/brands/$brand->id/logo/view")->assertForbidden();
    }

    public function test_regular_user_cannot_delete_brand_mail_logo()
    {
        $this->asRegularUser()->signIn();

        $brand = Brand::factory()->create();

        $this->deleteJson("/api/brands/$brand->id/logo/mail")->assertForbidden();
    }

    public function test_it_deletes_view_logo_when_deleting_brand()
    {
        $this->signIn();
        Storage::fake('public');

        $logo = UploadedFile::fake()->image('logo.jpg');
        $brand = Brand::factory()->create();

        $this->postJson("/api/brands/$brand->id/logo/view", [
            'logo_view' => $logo,
        ]);

        $brand->fresh()->delete();

        Storage::disk('public')->assertMissing("brands/{$logo->hashName()}");
    }

    public function test_it_deletes_mail_logo_when_deleting_brand()
    {
        $this->signIn();
        Storage::fake('public');

        $logo = UploadedFile::fake()->image('logo.jpg');
        $brand = Brand::factory()->create();

        $this->postJson("/api/brands/$brand->id/logo/mail", [
            'logo_mail' => $logo,
        ]);

        $brand->fresh()->delete();

        Storage::disk('public')->assertMissing("brands/{$logo->hashName()}");
    }
}
