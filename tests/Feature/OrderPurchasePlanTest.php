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
            'source' => Order::DEFAULT_SOURCE, 'status' => Order::DEFAULT_STATUS,
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
            'return_date' => '2034-06-04', 'source' => Order::DEFAULT_SOURCE, 'status' => Order::DEFAULT_STATUS,
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
