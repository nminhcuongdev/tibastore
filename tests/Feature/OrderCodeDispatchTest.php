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

    public function test_failed_dispatch_explains_shortage_and_links_to_the_exact_size_without_changing_stock(): void
    {
        $this->actingAs(User::create(['code' => 'repair-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $code = 'REPAIR-'.bin2hex(random_bytes(4));
        $large = Product::create(['code' => $code, 'name' => 'Test', 'size' => 'L', 'fabric' => '', 'stock_quantity' => 3]);
        $medium = Product::create(['code' => $code, 'name' => 'Test', 'size' => 'M', 'fabric' => '', 'stock_quantity' => 0]);
        $order = Order::create(['product_id' => $large->id, 'quantity' => 3, 'closer_name' => 'Test', 'order_name' => $code,
            'pickup_date' => '2034-01-01', 'event_date' => '2034-01-02', 'return_date' => '2034-01-03', 'status' => Order::DEFAULT_STATUS]);
        $order->items()->create(['product_id' => $large->id, 'quantity' => 1]);
        $order->items()->create(['product_id' => $medium->id, 'quantity' => 2]);
        $editUrl = route('orders.edit', ['order' => $order->id, 'repair_product' => $medium->id]).'#order_items';
        $response = $this->patchJson(route('orders.status', $order), ['status' => 'da_gui'])
            ->assertUnprocessable()->assertJsonValidationErrors('items')
            ->assertJsonPath('repair_actions.0.url', $editUrl)
            ->assertJsonPath('repair_actions.1.url', route('products.show', $medium));
        $message = $response->json('errors.items.0');
        $this->assertStringContainsString($code.' - size M', $message);
        $this->assertStringContainsString('cần 2, kho thực tế còn 0, thiếu 2', $message);
        $this->assertSame(Order::DEFAULT_STATUS, $order->fresh()->status);
        $this->assertSame(3, $large->fresh()->stock_quantity);
        $this->assertSame(0, $medium->fresh()->stock_quantity);
        $this->assertSame(0, (int) $order->items()->sum('stock_held'));
        $this->get($editUrl)->assertOk()->assertSee('repair_product');
    }

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
