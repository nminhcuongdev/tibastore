<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Services\OrderInventoryService;
use App\Services\OrderTurnaroundService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderTurnaroundTest extends TestCase
{
    use DatabaseTransactions;

    private function product(): Product
    {
        return Product::create(['code' => 'TURN-'.bin2hex(random_bytes(5)), 'name' => 'Áo thử',
            'fabric' => 'Lụa', 'size' => 'M', 'stock_quantity' => 2]);
    }

    private function booking(Product $product, string $pickup, string $return, int $quantity = 2): Order
    {
        $order = Order::create(['closer_name' => 'Test', 'order_name' => 'Đơn thử',
            'product_id' => $product->id, 'quantity' => $quantity, 'pickup_date' => $pickup,
            'event_date' => $pickup, 'return_date' => $return, 'status' => 'chua_cho_size']);
        $order->items()->create(['product_id' => $product->id, 'quantity' => $quantity]);
        return $order;
    }

    public function test_same_day_turnaround_is_bookable_but_strict_stock_warns(): void
    {
        $product = $this->product();
        $this->booking($product, '2030-10-21', '2030-10-23');
        $inventory = app(OrderInventoryService::class);
        $this->assertSame(2, $inventory->projectedAvailableQuantities([$product->id], '2030-10-23', '2030-10-25')[$product->id]);
        $this->assertSame(0, $inventory->projectedAvailableQuantities([$product->id], '2030-10-23', '2030-10-25', null, false)[$product->id]);
        $inventory->assertItemsAvailable([['product_id' => $product->id, 'quantity' => 2]], '2030-10-23', '2030-10-25');
        $this->assertCount(1, app(OrderTurnaroundService::class)->returns([$product->id], '2030-10-23'));
    }

    public function test_real_overlap_is_still_blocked(): void
    {
        $product = $this->product();
        $this->booking($product, '2030-10-21', '2030-10-24');
        $this->expectException(ValidationException::class);
        app(OrderInventoryService::class)->assertItemsAvailable(
            [['product_id' => $product->id, 'quantity' => 2]], '2030-10-23', '2030-10-25'
        );
    }

    public function test_one_day_bookings_and_multiple_new_orders_do_not_overbook(): void
    {
        $product = $this->product();
        $this->booking($product, '2030-10-23', '2030-10-23');
        $this->assertSame(0, app(OrderInventoryService::class)->projectedAvailableQuantities(
            [$product->id], '2030-10-23', '2030-10-24'
        )[$product->id]);
        $other = $this->product();
        $this->booking($other, '2030-10-21', '2030-10-23');
        $this->booking($other, '2030-10-23', '2030-10-25');
        $this->assertSame(0, app(OrderInventoryService::class)->projectedAvailableQuantities(
            [$other->id], '2030-10-23', '2030-10-24'
        )[$other->id]);
    }

    public function test_reminder_starts_one_day_before_and_stops_after_return(): void
    {
        $product = $this->product();
        $incoming = $this->booking($product, '2030-10-21', '2030-10-23');
        $outgoing = $this->booking($product, '2030-10-23', '2030-10-25');
        $service = app(OrderTurnaroundService::class);
        $forOrder = fn ($day) => $service->reminders(Carbon::parse($day))->filter(fn ($r) => $r['order']->id === $outgoing->id);
        $this->assertCount(0, $forOrder('2030-10-21'));
        $this->assertCount(1, $forOrder('2030-10-22'));
        $this->assertSame(2, $forOrder('2030-10-22')->first()['shortage']);
        $this->assertCount(1, $forOrder('2030-10-23'));
        $incoming->update(['status' => Order::RETURNED_STATUS]);
        $this->assertCount(0, $forOrder('2030-10-22'));
    }

    public function test_cancelled_orders_do_not_reserve_stock_or_trigger_reminders(): void
    {
        $product = $this->product();
        $incoming = $this->booking($product, '2030-10-21', '2030-10-23');
        $outgoing = $this->booking($product, '2030-10-23', '2030-10-25');
        $outgoing->update(['status' => 'huy_don']);
        $this->assertCount(0, app(OrderTurnaroundService::class)->reminders(Carbon::parse('2030-10-22'))
            ->filter(fn ($r) => $r['order']->id === $outgoing->id));
        $incoming->update(['status' => 'huy_don']);
        $this->assertSame(2, app(OrderInventoryService::class)->projectedAvailableQuantities(
            [$product->id], '2030-10-22', '2030-10-24'
        )[$product->id]);
    }

    public function test_stock_cannot_be_sent_until_incoming_goods_are_received(): void
    {
        $product = $this->product();
        $incoming = $this->booking($product, '2030-10-21', '2030-10-23');
        $outgoing = $this->booking($product, '2030-10-23', '2030-10-25');
        $inventory = app(OrderInventoryService::class);
        $incoming->update(['status' => 'da_gui']);
        $inventory->syncOrder($incoming);
        $this->assertSame(0, $product->fresh()->stock_quantity);
        $outgoing->update(['status' => 'da_gui']);
        try {
            $inventory->syncOrder($outgoing);
            $this->fail('Sending without physical stock must fail');
        } catch (ValidationException $exception) {
            $this->assertSame(0, $product->fresh()->stock_quantity);
        }
        $incoming->update(['status' => Order::RETURNED_STATUS]);
        $inventory->syncOrder($incoming);
        $inventory->syncOrder($outgoing);
        $this->assertSame(0, $product->fresh()->stock_quantity);
        $this->assertSame(2, $outgoing->fresh()->items->sum('stock_held'));
    }

    public function test_availability_save_and_reminder_popup_work_through_http(): void
    {
        $this->actingAs(\App\Models\User::create([
            'code' => 'turn-'.bin2hex(random_bytes(5)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false,
        ]));
        $product = $this->product();
        $product->update(['image_path' => 'products/turnaround-test.jpg']);
        $incoming = $this->booking($product, '2030-10-21', '2030-10-23');
        $this->postJson('/orders/availability', [
            'pickup_date' => '2030-10-23', 'return_date' => '2030-10-25', 'product_ids' => [$product->id],
        ])->assertOk()->assertJsonPath('availability.'.$product->id, 2)
            ->assertJsonPath('strict_availability.'.$product->id, 0)
            ->assertJsonPath('same_day_returns.'.$product->id.'.0.order_name', $incoming->order_name);
        $this->post('/orders', [
            'closer_name' => 'Test', 'order_name' => 'Đơn nối ngày', 'pickup_date' => '2030-10-23',
            'event_date' => '2030-10-24', 'return_date' => '2030-10-25',
            'source' => Order::DEFAULT_SOURCE, 'status' => Order::DEFAULT_STATUS,
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'rental_price' => 100000]],
        ])->assertSessionHasNoErrors()->assertRedirect();
        try {
            Carbon::setTestNow(Carbon::parse('2030-10-22 12:00:00', 'Asia/Ho_Chi_Minh'));
            $this->get('/orders/create')->assertOk()
                ->assertSee('Nhắc đòi đồ về sớm')->assertSee('Đơn nối ngày')
                ->assertSee('products/turnaround-test.jpg')
                ->assertSee('alt="Áo thử — mã '.$product->code.'"', false);
            $product->update(['image_path' => null]);
            $this->get('/orders/create')->assertOk()->assertSee('Chưa có ảnh');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_reminder_button_lists_future_returns_even_when_orders_are_filtered_out(): void
    {
        $this->actingAs(\App\Models\User::create([
            'code' => 'rem-'.bin2hex(random_bytes(5)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false,
        ]));
        $product = $this->product();
        $incoming = $this->booking($product, '2030-10-24', '2030-10-26');
        $outgoing = $this->booking($product, '2030-10-26', '2030-10-28');
        try {
            Carbon::setTestNow(Carbon::parse('2030-10-22 12:00:00', 'Asia/Ho_Chi_Minh'));
            $service = app(OrderTurnaroundService::class);
            $this->assertCount(0, $service->reminders());
            $this->assertCount(1, $service->reminders(null, true));
            $this->get('/orders?closer=nonexistent-'.bin2hex(random_bytes(5)))
                ->assertOk()->assertSee('Nhắc đòi đồ (1)')
                ->assertSee('data-open-return-reminders', false)
                ->assertSee('Toàn bộ đơn sắp tới')
                ->assertViewHas('returnReminders', fn ($rows) => $rows->contains(fn ($r) => $r['order']->id === $outgoing->id))
                ->assertViewHas('orders', fn ($orders) => $orders->total() === 0);
            $incoming->update(['status' => Order::RETURNED_STATUS]);
            $this->get('/orders')->assertOk()->assertSee('Nhắc đòi đồ (0)')
                ->assertSee('Hiện không có đơn cần đòi đồ về sớm.');
        } finally {
            Carbon::setTestNow();
        }
    }
}
