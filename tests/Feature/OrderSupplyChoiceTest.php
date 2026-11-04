<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\WorkshopOrder;
use App\Services\WorkshopArrivalReminderService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OrderSupplyChoiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_selecting_existing_od_links_it_without_creating_duplicate_or_changing_status(): void
    {
        $this->actingAs(User::create(['code' => 'adopt-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $product = Product::create(['code' => 'ADOPT-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'fabric' => '', 'size' => 'M', 'stock_quantity' => 20]);
        $od = WorkshopOrder::create(['product_code' => $product->code, 'supply_source' => WorkshopOrder::SUPPLY_OUTSIDE,
            'quantity' => 5, 'status' => WorkshopOrder::STATUS_ORDERED, 'size_quantities' => [['product_id' => $product->id, 'quantity' => 5]],
            'image_path' => 'workshop-orders/existing.jpg']);
        $data = ['closer_name' => 'Test', 'order_name' => $product->code,
            'pickup_date' => '2032-03-03', 'event_date' => '2032-03-04', 'return_date' => '2032-03-05',
            'source' => Order::DEFAULT_SOURCE, 'status' => Order::DEFAULT_STATUS,
            'items' => [['product_id' => $product->id, 'quantity' => 5, 'fulfillment_source' => 'mua_ngoai',
                'purchase_plan' => ['workshop_order_id' => $od->id, 'expected_date' => '2032-03-02',
                    'lines' => [['product_id' => $product->id, 'quantity' => 5]]]]]];
        $this->post('/orders', $data)->assertSessionHasNoErrors()->assertRedirect();
        $order = Order::where('order_name', $product->code)->latest('id')->firstOrFail();
        $this->assertSame($order->id, $od->fresh()->customer_order_id);
        $this->assertSame(WorkshopOrder::STATUS_ORDERED, $od->fresh()->status);
        $this->assertSame('workshop-orders/existing.jpg', $od->fresh()->image_path);
        $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
        $previousStringify = $pdo->getAttribute(\PDO::ATTR_STRINGIFY_FETCHES);
        try {
            // Some production drivers return foreign keys as numeric strings.
            $pdo->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, true);
            $this->put('/orders/'.$order->id, $data)->assertSessionHasNoErrors()->assertRedirect();
        } finally {
            $pdo->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, $previousStringify);
        }
        $this->assertSame(1, WorkshopOrder::where('product_code', $product->code)->count());
        $this->assertSame(5, $order->fresh()->items->sum('quantity'));
        $data['order_name'] .= '-other';
        $this->postJson('/orders', $data)->assertUnprocessable()->assertJsonValidationErrors('items');
        $this->assertSame($order->id, $od->fresh()->customer_order_id);
    }

    public function test_related_workshop_suggestions_filter_by_code_and_source_without_changing_orders(): void
    {
        $this->actingAs(User::create(['code' => 'related-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $code = 'RELATED-'.bin2hex(random_bytes(4));
        $data = ['product_code' => $code, 'supply_source' => WorkshopOrder::SUPPLY_OUTSIDE,
            'quantity' => 3, 'size_note' => 'M: 3', 'order_name' => 'Đơn đã od', 'status' => WorkshopOrder::STATUS_ORDERED];
        $match = WorkshopOrder::create($data);
        WorkshopOrder::create(array_replace($data, ['supply_source' => WorkshopOrder::SUPPLY_WORKSHOP]));
        WorkshopOrder::create(array_replace($data, ['product_code' => $code.'-OTHER']));
        $this->getJson(route('workshop-orders.related-orders', ['code' => $code, 'supply_source' => WorkshopOrder::SUPPLY_OUTSIDE]))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $match->id)->assertJsonPath('0.status', 'Đã od')
            ->assertJsonPath('0.quantity', 3)->assertJsonPath('0.size_note', 'M: 3');
        $this->assertSame(WorkshopOrder::STATUS_ORDERED, $match->fresh()->status);
        $this->assertNull($match->fresh()->customer_order_id);
        $this->getJson(route('workshop-orders.related-orders', ['code' => $code, 'supply_source' => 'invalid']))
            ->assertUnprocessable();
    }

    public function test_supply_choice_defaults_to_stock_and_prefills_linked_order_without_duplicates(): void
    {
        $this->actingAs(User::create(['code' => 'supply-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $product = Product::create(['code' => 'SUP-'.bin2hex(random_bytes(4)), 'name' => 'Áo thử',
            'fabric' => '', 'size' => 'M', 'stock_quantity' => 50]);
        $data = ['closer_name' => 'Test', 'order_name' => $product->code,
            'pickup_date' => '2032-03-03', 'event_date' => '2032-03-04', 'return_date' => '2032-03-05',
            'source' => Order::DEFAULT_SOURCE, 'status' => Order::DEFAULT_STATUS,
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'rental_price' => 10000]]];
        $this->post('/orders', $data)->assertSessionHasNoErrors()->assertRedirect();
        $order = Order::where('product_id', $product->id)->latest('id')->firstOrFail();
        $this->assertSame('stock', $order->items->first()->fulfillment_source);
        $staleUrl = route('workshop-orders.create', ['customer_order_id' => $order->id,
            'product_code' => $product->code, 'supply_source' => 'mua_ngoai']);
        $this->get($staleUrl)->assertRedirect(route('orders.edit', $order))->assertSessionHas('error');
        $data['items'][0]['fulfillment_source'] = 'mua_ngoai';
        $this->put('/orders/'.$order->id, $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('mua_ngoai', $order->fresh()->items->first()->fulfillment_source);
        $this->get('/orders/'.$order->id.'/edit')->assertOk()->assertSee('Cần od – Xưởng')->assertSee('Cần od – Mua ngoài')
            ->assertSee('"fulfillment_source":"mua_ngoai"', false);
        $url = route('workshop-orders.create', ['customer_order_id' => $order->id, 'product_code' => $product->code, 'supply_source' => 'mua_ngoai']);
        $this->get($url)->assertOk()->assertViewHas('workshopOrder', fn ($item) => $item->customer_order_id === $order->id
            && $item->quantity === 2 && $item->size_note === 'M: 2' && $item->supply_source === 'mua_ngoai');
        $workshopData = ['customer_order_id' => $order->id, 'product_code' => $product->code,
            'order_name' => $order->order_name, 'supply_source' => 'mua_ngoai', 'quantity' => 2,
            'status' => WorkshopOrder::STATUS_NOT_YET];
        $this->postJson('/workshop-orders', $workshopData)->assertCreated();
        $item = WorkshopOrder::where('customer_order_id', $order->id)->firstOrFail();
        $this->get($url)->assertRedirect(route('workshop-orders.index', ['focus' => $item->id]).'#workshop-row-'.$item->id);
        $order->update(['order_name' => 'Tên đã đổi']);
        $reminders = app(WorkshopArrivalReminderService::class)->reminders(Carbon::parse('2032-03-01', 'Asia/Ho_Chi_Minh'));
        $this->assertTrue($reminders->contains(fn ($row) => $row['item']->id === $item->id && $row['order']->id === $order->id));
        $data['items'][0]['fulfillment_source'] = 'invalid';
        $this->putJson('/orders/'.$order->id, $data)->assertUnprocessable()->assertJsonValidationErrors('items.0.fulfillment_source');
    }
}
