<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderInventoryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrderCodeDispatchTest extends TestCase
{
    use DatabaseTransactions;

    public function test_early_dispatch_updates_only_that_code_and_whole_order_dispatch_does_not_charge_it_twice(): void
    {
        $this->actingAs(User::create(['code' => 'dispatch-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $code = 'SEND-'.bin2hex(random_bytes(4));
        $base = ['name' => 'Test', 'fabric' => '', 'stock_quantity' => 5];
        $small = Product::create($base + ['code' => $code, 'size' => 'S']);
        $medium = Product::create($base + ['code' => $code, 'size' => 'M']);
        $other = Product::create($base + ['code' => $code.'-OTHER', 'size' => 'L']);
        $order = Order::create(['product_id' => $small->id, 'quantity' => 2, 'closer_name' => 'Test', 'order_name' => $code,
            'pickup_date' => '2032-01-01', 'event_date' => '2032-01-02', 'return_date' => '2032-01-03',
            'status' => Order::DEFAULT_STATUS]);
        foreach ([$small, $medium, $other] as $product) {
            $order->items()->create(['product_id' => $product->id, 'quantity' => 2]);
        }
        $url = route('orders.code-dispatch', $order);
        $this->patch($url, ['code' => $code, 'dispatched' => 1])->assertSessionHasNoErrors()->assertRedirect();
        $this->patch($url, ['code' => $code, 'dispatched' => 1])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame([3, 3, 5], [$small->fresh()->stock_quantity, $medium->fresh()->stock_quantity, $other->fresh()->stock_quantity]);
        $this->assertSame(4, app(OrderInventoryService::class)->rentedOutQuantitiesByCode()[$code]);
        $this->assertSame(Order::DEFAULT_STATUS, $order->fresh()->status);
        $this->patch($url, ['code' => $code, 'dispatched' => 0])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(5, $small->fresh()->stock_quantity);
        $this->patch($url, ['code' => $code, 'dispatched' => 1])->assertSessionHasNoErrors()->assertRedirect();
        $this->patchJson(route('orders.status', $order), ['status' => 'da_gui'])->assertOk();
        $this->assertSame([3, 3, 3], [$small->fresh()->stock_quantity, $medium->fresh()->stock_quantity, $other->fresh()->stock_quantity]);
        $this->patchJson(route('orders.status', $order), ['status' => Order::RETURNED_STATUS])->assertOk();
        $this->assertSame([5, 5, 5], [$small->fresh()->stock_quantity, $medium->fresh()->stock_quantity, $other->fresh()->stock_quantity]);
    }
}
