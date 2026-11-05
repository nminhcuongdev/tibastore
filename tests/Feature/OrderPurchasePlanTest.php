<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductExpectedReceipt;
use App\Models\User;
use App\Models\WorkshopOrder;
use App\Services\OrderInventoryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OrderPurchasePlanTest extends TestCase
{
    use DatabaseTransactions;

    public function test_confirmed_workshop_sizes_are_available_after_the_first_booking_returns(): void
    {
        $product = Product::create(['code' => 'FORECAST-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'fabric' => '', 'size' => 'M', 'stock_quantity' => 0]);
        $od = WorkshopOrder::create(['product_code' => $product->code, 'supply_source' => 'mua_ngoai',
            'status' => WorkshopOrder::STATUS_ORDERED, 'returned_date' => '2032-11-07', 'quantity' => 5,
            'size_quantities' => [['product_id' => $product->id, 'quantity' => 5]]]);
        $first = Order::create(['product_id' => $product->id, 'quantity' => 5, 'order_name' => 'First',
            'closer_name' => 'Test', 'status' => Order::DEFAULT_STATUS, 'pickup_date' => '2032-11-07',
            'event_date' => '2032-11-08', 'return_date' => '2032-11-09']);
        $first->items()->create(['product_id' => $product->id, 'quantity' => 5]);
        $inventory = app(OrderInventoryService::class);
        $ids = [$product->id];
        $this->assertSame(0, $inventory->projectedAvailableQuantities($ids, '2032-11-06', '2032-11-06')[$product->id]);
        $this->assertSame(0, $inventory->projectedAvailableQuantities($ids, '2032-11-08', '2032-11-08')[$product->id]);
        $this->assertSame(5, $inventory->projectedAvailableQuantities($ids, '2032-11-15', '2032-11-22')[$product->id]);
        ProductExpectedReceipt::create(['workshop_order_id' => $od->id, 'product_id' => $product->id,
            'expected_receive_date' => '2032-11-07', 'expected_receive_quantity' => 5]);
        $this->assertSame(5, $inventory->projectedAvailableQuantities($ids, '2032-11-15', '2032-11-22')[$product->id]);
        $this->assertSame(0, $product->fresh()->stock_quantity);
        $next = Order::create(['product_id' => $product->id, 'quantity' => 5, 'order_name' => 'Next booking',
            'closer_name' => 'Test', 'status' => Order::DEFAULT_STATUS, 'pickup_date' => '2032-11-15',
            'event_date' => '2032-11-16', 'return_date' => '2032-11-22']);
        $next->items()->create(['product_id' => $product->id, 'quantity' => 5]);
        $this->assertTrue($inventory->shortagesForOrder($next)->isEmpty());
        $od->update(['returned_date' => '2032-11-17']);
        $this->assertSame(5, $inventory->shortagesForOrder($next->fresh())->first()['shortage']);
        $od->update(['returned_date' => '2032-11-07', 'stocked_at' => now()]);
        $product->update(['stock_quantity' => 3]);
        $warnings = $inventory->bookingShortages($product->code);
        $warning = $warnings->firstWhere('order_name', 'Next booking');
        $this->assertSame(2, $warning['shortage']);
        $this->assertSame(route('orders.edit', $next), $warning['url']);
    }

    public function test_workshop_actual_receipt_edits_only_update_customer_order_when_requested(): void
    {
        $this->actingAs(User::create(['code' => 'actual-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $code = 'ACTUAL-'.bin2hex(random_bytes(4));
        $product = Product::create(['code' => $code, 'name' => 'Test', 'size' => 'M', 'fabric' => '', 'stock_quantity' => 10]);
        $plan = ['expected_date' => '2039-01-01', 'lines' => [['product_id' => $product->id, 'quantity' => 3]],
            'applied_lines' => [['product_id' => $product->id, 'quantity' => 3]], 'updated_once' => true];
        $this->post('/orders', ['closer_name' => 'Test', 'order_name' => $code, 'pickup_date' => '2039-01-02',
            'event_date' => '2039-01-03', 'return_date' => '2039-01-04', 'status' => Order::DEFAULT_STATUS,
            'source' => Order::DEFAULT_SOURCE, 'region' => 'tinh_mb', 'items' => [['product_id' => $product->id, 'quantity' => 13,
                'rental_price' => 10000, 'note' => 'Giữ ghi chú', 'fulfillment_source' => 'mua_ngoai', 'purchase_plan' => $plan]]])
            ->assertSessionHasNoErrors();
        $order = Order::where('order_name', $code)->firstOrFail();
        $workshop = WorkshopOrder::where('customer_order_id', $order->id)->firstOrFail();
        $edit = ['product_code' => $code, 'supply_source' => 'mua_ngoai', 'quantity' => 5, 'unit_price' => 0,
            'status' => WorkshopOrder::STATUS_NOT_YET, 'size_quantities' => [['product_id' => $product->id, 'quantity' => 5]]];
        $this->putJson('/workshop-orders/'.$workshop->id, $edit)->assertOk()->assertJsonPath('quantity', 5);
        $this->assertSame(13, (int) $order->items()->sum('quantity'));
        app(\App\Services\OrderPurchasePlanService::class)->sync($order->fresh());
        $this->assertSame(5, $workshop->fresh()->quantity);
        $this->assertSame(5, ProductExpectedReceipt::where('workshop_order_id', $workshop->id)->firstOrFail()->expected_receive_quantity);
        $this->postJson('/workshop-orders/'.$workshop->id.'/update-customer-order')->assertOk();
        $this->assertSame(15, (int) $order->items()->sum('quantity'));
        $this->assertSame(150000, $order->fresh()->total_amount);
        $this->assertSame('Giữ ghi chú', $order->items()->first()->note);
        $this->postJson('/workshop-orders/'.$workshop->id.'/update-customer-order')->assertOk();
        $this->assertSame(15, (int) $order->items()->sum('quantity'));
        $edit['size_quantities'][0]['quantity'] = 2;
        $this->putJson('/workshop-orders/'.$workshop->id, $edit)->assertOk()->assertJsonPath('quantity', 2);
        $this->assertSame(15, (int) $order->items()->sum('quantity'));
        $this->postJson('/workshop-orders/'.$workshop->id.'/update-customer-order')->assertOk();
        $this->assertSame(12, (int) $order->items()->sum('quantity'));
        $edit['size_quantities'][0]['quantity'] = 4;
        $this->putJson('/workshop-orders/'.$workshop->id, $edit)->assertOk();
        $this->postJson('/workshop-orders/'.$workshop->id.'/stock')->assertOk();
        $this->assertSame(14, $product->fresh()->stock_quantity);
        $this->assertSame(12, (int) $order->items()->sum('quantity'));
        $this->postJson('/workshop-orders/'.$workshop->id.'/stock')->assertOk();
        $this->assertSame(14, $product->fresh()->stock_quantity);
    }

    public function test_additional_purchase_preserves_existing_stock_size_and_notes_when_saved_again(): void
    {
        $this->actingAs(User::create(['code' => 'append-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $code = 'APPEND-'.bin2hex(random_bytes(4));
        $large = Product::create(['code' => $code, 'name' => 'Test', 'size' => 'L', 'fabric' => '', 'stock_quantity' => 1]);
        $medium = Product::create(['code' => $code, 'name' => 'Test', 'size' => 'M', 'fabric' => '', 'stock_quantity' => 0]);
        $plan = ['expected_date' => '2038-01-01', 'lines' => [['product_id' => $medium->id, 'quantity' => 1]],
            'applied_lines' => [['product_id' => $medium->id, 'quantity' => 1]]];
        $data = ['closer_name' => 'Test', 'order_name' => $code, 'pickup_date' => '2038-01-02',
            'event_date' => '2038-01-03', 'return_date' => '2038-01-04', 'status' => Order::DEFAULT_STATUS,
            'source' => Order::DEFAULT_SOURCE, 'region' => 'tinh_mb', 'items' => [
                ['product_id' => $large->id, 'quantity' => 1, 'rental_price' => 90000, 'note' => 'Giữ ghi chú L', 'fulfillment_source' => 'mua_ngoai', 'purchase_plan' => $plan],
                ['product_id' => $medium->id, 'quantity' => 1, 'rental_price' => 90000, 'note' => '1 M mua thêm', 'fulfillment_source' => 'mua_ngoai', 'purchase_plan' => $plan],
            ]];
        $this->post('/orders', $data)->assertSessionHasNoErrors();
        $order = Order::where('order_name', $code)->firstOrFail();
        $data['items'][0]['purchase_plan']['applied_lines'][] = ['product_id' => 2147483647, 'quantity' => 1];
        $this->put('/orders/'.$order->id, $data)->assertSessionHasNoErrors();
        $this->assertSame($plan['applied_lines'], $order->items()->where('product_id', $large->id)->firstOrFail()->purchase_plan['applied_lines']);
        $this->assertSame(2, (int) $order->items()->sum('quantity'));
        $this->assertSame('Giữ ghi chú L', $order->items()->where('product_id', $large->id)->firstOrFail()->note);
        $this->assertSame('1 M mua thêm', $order->items()->where('product_id', $medium->id)->firstOrFail()->note);
        $this->assertSame(1, WorkshopOrder::where('customer_order_id', $order->id)->firstOrFail()->quantity);
        $this->assertSame(1, $large->fresh()->stock_quantity);
        $this->assertSame(0, $medium->fresh()->stock_quantity);
    }

    public function test_confirmed_purchase_sizes_save_to_order_and_forecasts_without_physical_stock(): void
    {
        $this->actingAs(User::create(['code' => 'sizes-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $code = 'SIZES-'.bin2hex(random_bytes(4));
        $lines = [];
        foreach (['S' => 5, 'M' => 4, 'L' => 5, 'XL' => 4] as $size => $quantity) {
            $product = Product::create(['code' => $code, 'name' => 'Test', 'size' => $size, 'fabric' => '', 'stock_quantity' => 0]);
            $lines[] = ['product_id' => $product->id, 'quantity' => $quantity];
        }
        $plan = ['expected_date' => '2035-04-01', 'lines' => $lines, 'applied_lines' => $lines, 'updated_once' => true];
        $items = array_map(fn ($line) => $line + ['size_pending' => false, 'rental_price' => 10000,
            'fulfillment_source' => 'mua_ngoai', 'purchase_plan' => $plan], $lines);
        $this->post('/orders', ['closer_name' => 'Test', 'order_name' => $code, 'pickup_date' => '2035-04-02',
            'event_date' => '2035-04-03', 'return_date' => '2035-04-04', 'status' => Order::DEFAULT_STATUS,
            'source' => Order::DEFAULT_SOURCE, 'region' => 'tinh_mb', 'items' => $items])->assertSessionHasNoErrors()->assertRedirect();
        $order = Order::where('order_name', $code)->firstOrFail();
        $this->assertSame(4, $order->items()->count());
        $this->assertSame(18, (int) $order->items()->sum('quantity'));
        $workshop = WorkshopOrder::where('customer_order_id', $order->id)->firstOrFail();
        $this->assertSame($lines, $order->items->first()->purchase_plan['applied_lines']);
        $this->assertTrue($order->items->first()->purchase_plan['updated_once']);
        $this->assertArrayNotHasKey('applied_lines', $workshop->purchase_plan);
        $this->assertArrayNotHasKey('updated_once', $workshop->purchase_plan);
        $this->assertSame(18, $workshop->quantity);
        $this->assertSame(4, ProductExpectedReceipt::where('workshop_order_id', $workshop->id)->count());
        app(OrderInventoryService::class)->syncDueOrders(Carbon::parse('2035-04-05'));
        $this->assertSame(0, (int) Product::where('code', $code)->sum('stock_quantity'));
        $this->get('/orders/'.$order->id.'/edit')->assertOk()->assertSee('Update vào đơn');
    }

    public function test_manual_forecasts_do_not_automatically_enter_stock_when_due(): void
    {
        $this->actingAs(User::create(['code' => 'forecast-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $product = Product::create(['code' => 'FORECAST-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'fabric' => '', 'size' => 'M', 'stock_quantity' => 0]);
        $receipt = ProductExpectedReceipt::create(['product_id' => $product->id,
            'expected_receive_date' => '2020-01-01', 'expected_receive_quantity' => 5]);
        app(OrderInventoryService::class)->syncDueOrders(Carbon::parse('2040-01-01'));
        $this->get('/products')->assertOk();
        $this->assertSame(0, $product->fresh()->stock_quantity);
        $this->assertNull($receipt->fresh()->received_at);
        $this->put('/products/'.$product->id, ['code' => $product->code, 'name' => 'Test',
            'size' => 'M', 'stock_quantity' => 5])->assertSessionHasNoErrors();
        $this->assertSame(5, $product->fresh()->stock_quantity);
    }

    public function test_purchase_sizes_sync_without_duplicates_and_only_enter_stock_after_arrival(): void
    {
        $this->actingAs(User::create(['code' => 'plan-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $code = 'PLAN-'.bin2hex(random_bytes(4));
        $m = Product::create(['code' => $code, 'name' => 'Áo thử', 'fabric' => '', 'size' => 'M', 'stock_quantity' => 0]);
        $l = Product::create(['code' => $code, 'name' => 'Áo thử', 'fabric' => '', 'size' => 'L', 'stock_quantity' => 0]);
        $plan = ['expected_date' => '2033-04-10', 'lines' => [['product_id' => $m->id, 'quantity' => 4], ['product_id' => $l->id, 'quantity' => 5]]];
        $data = ['closer_name' => 'Test', 'order_name' => $code,
            'pickup_date' => '2033-04-11', 'event_date' => '2033-04-12', 'return_date' => '2033-04-13',
            'source' => Order::DEFAULT_SOURCE, 'region' => 'tinh_mb', 'status' => Order::DEFAULT_STATUS,
            'items' => [['product_id' => $m->id, 'size_pending' => true, 'quantity' => 9, 'rental_price' => 10000,
                'fulfillment_source' => 'mua_ngoai', 'purchase_plan' => json_encode($plan)]]];
        $this->post('/orders', $data)->assertSessionHasNoErrors()->assertRedirect();
        $order = Order::where('product_id', $m->id)->firstOrFail();
        $od = WorkshopOrder::where('customer_order_id', $order->id)->firstOrFail();
        $this->assertSame(9, $od->quantity);
        $this->assertSame('M: 4, L: 5', $od->size_note);
        $this->assertSame(2, ProductExpectedReceipt::where('workshop_order_id', $od->id)->count());
        $this->put('/orders/'.$order->id, $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(1, WorkshopOrder::where('customer_order_id', $order->id)->count());
        $this->assertSame(2, ProductExpectedReceipt::where('workshop_order_id', $od->id)->count());
        $plan['lines'][0]['quantity'] = 3;
        $data['items'][0]['purchase_plan'] = json_encode($plan);
        $this->put('/orders/'.$order->id, $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(8, $od->fresh()->quantity);
        $this->assertSame(3, ProductExpectedReceipt::where('workshop_order_id', $od->id)->where('product_id', $m->id)->firstOrFail()->expected_receive_quantity);
        $projected = app(OrderInventoryService::class)->projectedAvailableQuantities([$m->id, $l->id], '2033-04-11', '2033-04-13', $order->id);
        $this->assertSame(3, $projected[$m->id]);
        $this->assertSame(5, $projected[$l->id]);
        app(OrderInventoryService::class)->syncDueOrders(Carbon::parse('2033-04-11'));
        $this->assertSame(0, $m->fresh()->stock_quantity);
        $this->patch('/workshop-orders/'.$od->id.'/status', ['status' => WorkshopOrder::STATUS_ARRIVED])->assertRedirect();
        $this->assertSame(0, $m->fresh()->stock_quantity);
        $this->post('/workshop-orders/'.$od->id.'/stock')->assertRedirect();
        $this->assertSame(3, $m->fresh()->stock_quantity);
        $this->assertSame(5, $l->fresh()->stock_quantity);
        $this->post('/workshop-orders/'.$od->id.'/stock')->assertRedirect();
        $this->assertSame(3, $m->fresh()->stock_quantity);
        app(OrderInventoryService::class)->syncDueOrders(Carbon::parse('2033-04-11'));
        $this->assertSame(3, $m->fresh()->stock_quantity);
        $this->put('/orders/'.$order->id, $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(3, $m->fresh()->stock_quantity);
        $entry = \App\Models\WorkshopStockTransfer::where('workshop_order_id', $od->id)->firstOrFail();
        $this->postJson(route('workshop-orders.undo-stock', $entry))->assertOk();
        $this->assertSame(0, $m->fresh()->stock_quantity);
        $this->assertSame(2, ProductExpectedReceipt::where('workshop_order_id', $od->id)->whereNull('received_at')->count());
        $this->postJson('/workshop-orders/'.$od->id.'/stock')->assertOk();
        $this->assertSame(3, $m->fresh()->stock_quantity);
        $plan['expected_date'] = '2033-04-20';
        $data['items'][0]['purchase_plan'] = json_encode($plan);
        $this->putJson('/orders/'.$order->id, $data)->assertUnprocessable()->assertJsonValidationErrors('items.0.purchase_plan.expected_date');
    }

    public function test_planned_purchase_must_cover_shortage_and_clears_only_its_own_pending_receipts(): void
    {
        $this->actingAs(User::create(['code' => 'cover-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $p = Product::create(['code' => 'BUY-'.bin2hex(random_bytes(4)), 'name' => 'Test', 'fabric' => '', 'size' => 'M', 'stock_quantity' => 0]);
        $plan = ['expected_date' => '2034-06-01', 'lines' => [['product_id' => $p->id, 'quantity' => 1]]];
        $data = ['closer_name' => 'Test', 'order_name' => $p->code, 'pickup_date' => '2034-06-02', 'event_date' => '2034-06-03',
            'return_date' => '2034-06-04', 'source' => Order::DEFAULT_SOURCE, 'region' => 'tinh_mb', 'status' => Order::DEFAULT_STATUS,
            'items' => [['product_id' => $p->id, 'quantity' => 2, 'fulfillment_source' => 'xuong_may', 'purchase_plan' => $plan]]];
        $this->postJson('/orders', $data)->assertUnprocessable();
        $this->assertSame(0, WorkshopOrder::where('product_code', $p->code)->count());
        $data['items'][0]['purchase_plan']['lines'][0]['quantity'] = 2;
        $this->post('/orders', $data)->assertSessionHasNoErrors()->assertRedirect();
        $order = Order::where('product_id', $p->id)->firstOrFail();
        $od = WorkshopOrder::where('customer_order_id', $order->id)->firstOrFail();
        $manual = ProductExpectedReceipt::create(['product_id' => $p->id, 'expected_receive_date' => '2034-06-01', 'expected_receive_quantity' => 1]);
        $p->update(['stock_quantity' => 2]);
        $data['items'][0]['fulfillment_source'] = 'stock';
        $data['items'][0]['purchase_plan'] = null;
        $this->put('/orders/'.$order->id, $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertNull($od->fresh());
        $this->assertSame(0, ProductExpectedReceipt::where('workshop_order_id', $od->id)->count());
        $this->assertNotNull($manual->fresh());
    }
}
