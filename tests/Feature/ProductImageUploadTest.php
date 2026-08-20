<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageUploadTest extends TestCase
{
    public function test_admin_can_upload_image_to_minio()
    {
        $product = Product::first();
        if (!$product) {
            $this->markTestSkipped('No products in database.');
        }

        // Create dummy image file
        $file = UploadedFile::fake()->image('test_product.jpg');

        $response = $this->post(route('admin.products.update', $product->id), [
            'name' => $product->name,
            'price' => $product->price,
            'original_price' => $product->original_price,
            'unit' => $product->unit,
            'origin' => $product->origin,
            'desc' => $product->desc,
            'pack' => $product->pack,
            'image' => $file,
        ]);

        $response->assertRedirect();
        
        $product->refresh();
        $this->assertNotNull($product->image);
        $this->assertStringContainsString('products/', $product->image);
        
        // Assert file exists on the actual S3/MinIO disk
        $exists = Storage::disk('s3')->exists($product->image);
        $this->assertTrue($exists, 'File does not exist on S3');

        // Check image URL structure
        $url = $product->image_url;
        $this->assertNotNull($url);
        $this->assertStringContainsString('http://192.168.170.14:9000/fruit/products/', $url);

        // Delete test file
        Storage::disk('s3')->delete($product->image);
    }
}
