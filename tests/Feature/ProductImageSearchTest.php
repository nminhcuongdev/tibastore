<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductImageSearchTest extends TestCase
{
    use DatabaseTransactions;

    public function test_image_search_requires_login(): void
    {
        $this->get('/products/image-search')->assertRedirect('/login');
        $this->getJson('/products/image-search/catalog')->assertUnauthorized();
    }

    public function test_catalog_groups_sizes_and_excludes_inactive_missing_or_unsafe_images(): void
    {
        $this->actingAs(User::create(['code' => 'image-'.bin2hex(random_bytes(5)),
            'name' => 'Test', 'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $code = 'IMG-'.bin2hex(random_bytes(5));
        $base = ['code' => $code, 'name' => 'Váy thử ảnh', 'fabric' => 'Lụa', 'image_path' => 'products/test-image.jpg'];
        $first = Product::create($base + ['size' => 'M', 'stock_quantity' => 2]);
        Product::create($base + ['size' => 'L', 'stock_quantity' => 3]);
        Product::create(['code' => $code.'-hidden', 'name' => 'Hidden', 'fabric' => 'Lụa',
            'size' => 'M', 'image_path' => 'products/hidden.jpg', 'discontinued_at' => now()]);
        Product::create(['code' => $code.'-empty', 'name' => 'Empty', 'fabric' => 'Lụa', 'size' => 'M']);
        Product::create(['code' => $code.'-unsafe', 'name' => 'Unsafe', 'fabric' => 'Lụa',
            'size' => 'M', 'image_path' => 'https://example.com/image.jpg']);
        $this->get('/products/image-search')->assertOk()->assertSee('Tìm mã bằng ảnh')->assertSee('Ctrl+V');
        $response = $this->getJson('/products/image-search/catalog')->assertOk();
        $products = collect($response->json('products'));
        $match = $products->firstWhere('code', $code);
        $this->assertNotNull($match);
        $this->assertSame(5, $match['quantity']);
        $this->assertCount(1, $match['images']);
        $this->assertSame(route('products.show', $first), $match['url']);
        foreach (['-hidden', '-empty', '-unsafe'] as $suffix) {
            $this->assertNull($products->firstWhere('code', $code.$suffix));
        }
    }
}
