<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductStockBreakdownTest extends TestCase
{
    use DatabaseTransactions;

    public function test_grouped_stock_can_be_edited_inline_without_changing_rental_links(): void
    {
        $this->actingAs(User::create(['code' => 'inline-stock-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $product = Product::create(['code' => 'INLINE-'.bin2hex(random_bytes(4)), 'name' => 'Test', 'fabric' => '', 'size' => 'S', 'stock_quantity' => 2]);
        $order = Order::create(['product_id' => $product->id, 'closer_name' => 'Test', 'order_name' => 'Đang thuê',
            'pickup_date' => '2031-01-01', 'event_date' => '2031-01-02', 'return_date' => '2031-01-03',
            'status' => 'da_gui', 'quantity' => 2, 'stock_decreased_at' => now()]);
        $rental = $order->items()->create(['product_id' => $product->id, 'quantity' => 2, 'stock_held' => 2]);
        $data = ['code' => $product->code, 'name' => 'Tên đã sửa', 'rental_price_text' => 'Liên hệ',
            'variants' => [['id' => $product->id, 'size' => 'S', 'stock_quantity' => 3, 'original_quantity' => 2],
                ['size' => 'M', 'stock_quantity' => 2, 'original_quantity' => 0]]];
        $response = $this->patchJson(route('products.inline-stock', $product), $data)->assertOk();
        $this->assertSame(3, $product->fresh()->stock_quantity);
        $this->assertSame('Tên đã sửa', $product->fresh()->name);
        $this->assertSame($product->id, $rental->fresh()->product_id);
        $this->assertSame(2, (int) $rental->fresh()->stock_held);
        $this->assertSame(2, Product::where('code', $product->code)->count());
        $this->get(route('products.show', $product))->assertOk()->assertSee('data-stock-editor', false)
            ->assertDontSee('href="'.route('products.edit', $product).'"', false);
        $this->patchJson(route('products.inline-stock', $product), $data)->assertUnprocessable();
        $this->assertSame(3, $product->fresh()->stock_quantity);
    }

    public function test_product_shows_stock_in_warehouse_and_out_on_rental_separately(): void
    {
        $this->actingAs(User::create(['code' => 'stock-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $product = Product::create(['code' => 'SPLIT-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'fabric' => 'Lụa', 'size' => 'M', 'stock_quantity' => 5]);
        $order = Order::create(['product_id' => $product->id, 'closer_name' => 'Test', 'order_name' => 'Test',
            'pickup_date' => '2031-01-01', 'event_date' => '2031-01-02', 'return_date' => '2031-01-03',
            'status' => 'da_gui', 'quantity' => 5, 'stock_decreased_at' => now()]);
        $order->items()->create(['product_id' => $product->id, 'quantity' => 5, 'stock_held' => 5]);
        $this->get('/products/'.$product->id)->assertOk()
            ->assertViewHas('totalQuantity', fn ($value) => (int) $value === 5)
            ->assertViewHas('totalRented', 5)
            ->assertSee('Tổng số hàng')->assertSee('Đang cho thuê')->assertSee('Còn trong kho');
    }
}
