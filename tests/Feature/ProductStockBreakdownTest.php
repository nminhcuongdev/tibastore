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
