<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrderPurchaseIndicatorTest extends TestCase
{
    use DatabaseTransactions;

    public function test_sun_marks_orders_with_outside_or_workshop_items_only(): void
    {
        $this->actingAs(User::create(['code' => 'sun-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $product = Product::create(['code' => 'SUN-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'size' => 'M', 'fabric' => '', 'stock_quantity' => 10]);
        $orders = [];
        foreach (['stock', 'xuong_may', 'mua_ngoai'] as $source) {
            $order = Order::create(['product_id' => $product->id, 'quantity' => 1, 'closer_name' => 'Test',
                'order_name' => 'SUN-'.$source, 'pickup_date' => '2037-01-01', 'event_date' => '2037-01-02',
                'return_date' => '2037-01-03', 'status' => Order::DEFAULT_STATUS]);
            $order->items()->create(['product_id' => $product->id, 'quantity' => 1, 'fulfillment_source' => $source]);
            $orders[$source] = $order;
        }
        $this->get('/orders')->assertOk()
            ->assertSee('data-purchase-needed="'.$orders['xuong_may']->id.'"', false)
            ->assertSee('data-purchase-needed="'.$orders['mua_ngoai']->id.'"', false)
            ->assertDontSee('data-purchase-needed="'.$orders['stock']->id.'"', false)
            ->assertSee('☀️');
        $orders['mua_ngoai']->items()->update(['fulfillment_source' => 'stock']);
        $this->get('/orders')->assertOk()
            ->assertDontSee('data-purchase-needed="'.$orders['mua_ngoai']->id.'"', false);
    }
}
