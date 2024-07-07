<?php
 

namespace Modules\Brands\Tests\Feature;

use Modules\Brands\App\Models\Brand;
use Modules\Documents\App\Models\Document;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class BrandModelTest extends TestCase
{
    public function test_brand_has_logo_view_url()
    {
        $brand = Brand::factory()->make(['logo_view' => 'brands/logo.png']);

        $this->assertSame(url('storage/'.$brand->logo_view), $brand->logo_view_url);
    }

    public function test_brand_has_logo_mail_url()
    {
        $brand = Brand::factory()->make(['logo_mail' => 'brands/logo.png']);

        $this->assertSame(url('storage/'.$brand->logo_mail), $brand->logo_mail_url);
    }

    public function test_brand_has_pdf_font()
    {
        $brand = Brand::factory()->make(['config' => [
            'pdf' => ['font' => 'Almendra Display, cursive'],
        ]]);

        $font = $brand->pdfFont();

        $this->assertIsArray($font);
        $this->assertSame('Almendra Display, cursive', $font['font-family']);
        $this->assertSame('Almendra Display', $font['name']);
    }

    public function test_brand_with_documents_cannot_be_deleted()
    {
        $brand = Brand::factory()->has(Document::factory())->create();

        try {
            $brand->delete();
            $this->assertFalse(true, 'Brand with documents was deleted.');
        } catch (HttpException) {
            $this->assertTrue(true);
        }
    }

    public function test_brand_with_trashed_documents_cannot_be_deleted()
    {
        $brand = Brand::factory()->has(Document::factory()->trashed())->create();

        try {
            $brand->delete();
            $this->assertFalse(true, 'Brand with documents was deleted.');
        } catch (HttpException) {
            $this->assertTrue(true);
        }
    }
}
