<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductExpectedReceipt;
use App\Models\User;
use App\Models\WorkshopOrder;
use App\Services\StockReceiptReminderService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StockReceiptReminderTest extends TestCase
{
    use DatabaseTransactions;

    public function test_due_and_arrived_goods_are_reminded_until_explicit_stock_transfer(): void
    {
        $this->actingAs(User::create(['code' => 'receipt-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $this->travelTo(Carbon::parse('2036-01-09 18:00:00', 'UTC')); // Jan 10 in Vietnam.
        try {
            $product = Product::create(['code' => 'RECEIPT-'.bin2hex(random_bytes(4)), 'name' => 'Test',
                'size' => 'M', 'fabric' => '', 'stock_quantity' => 0]);
            $make = fn ($date, $status = WorkshopOrder::STATUS_ORDERED) => WorkshopOrder::create([
                'product_code' => $product->code, 'supply_source' => WorkshopOrder::SUPPLY_OUTSIDE,
                'quantity' => 2, 'returned_date' => $date, 'status' => $status,
                'size_quantities' => [['product_id' => $product->id, 'quantity' => 2]]]);
            $due = $make('2036-01-10');
            $overdue = $make('2036-01-09');
            $arrived = $make(null, WorkshopOrder::STATUS_ARRIVED);
            $future = $make('2036-01-11');
            $noDate = $make(null);
            $stocked = $make('2036-01-09'); $stocked->update(['stocked_at' => now()]);
            $cancelled = Order::create(['product_id' => $product->id, 'quantity' => 1, 'closer_name' => 'Test',
                'order_name' => 'Cancelled', 'pickup_date' => '2036-01-10', 'event_date' => '2036-01-10',
                'return_date' => '2036-01-11', 'status' => 'huy_don']);
            $cancelledLine = $make('2036-01-10'); $cancelledLine->update(['customer_order_id' => $cancelled->id]);
            $legacy = $make('2036-01-09');
            ProductExpectedReceipt::create(['workshop_order_id' => $legacy->id, 'product_id' => $product->id,
                'expected_receive_date' => '2036-01-09', 'expected_receive_quantity' => 2, 'received_at' => now()]);
            $result = app(StockReceiptReminderService::class)->reminders();
            $this->assertSame('2036-01-10', $result['today']);
            $this->assertSame([$overdue->id, $due->id, $arrived->id], $result['workshop']->pluck('id')->all());
            foreach (['/orders?q=no-match', '/products', '/workshop-orders?supply_source=xuong_may'] as $url) {
                $this->get($url)->assertOk()->assertSee('Nhắc nhập kho (3)')->assertSee(route('workshop-orders.index', ['focus' => $due->id]));
            }
            $this->assertSame(0, $product->fresh()->stock_quantity);
            $this->postJson('/workshop-orders/'.$due->id.'/stock')->assertOk();
            $this->assertSame(2, $product->fresh()->stock_quantity);
            $this->assertFalse(app(StockReceiptReminderService::class)->reminders()['workshop']->contains('id', $due->id));
        } finally { $this->travelBack(); }
    }

    public function test_manual_forecasts_have_a_reminder_without_receiving_stock(): void
    {
        $product = Product::create(['code' => 'MANUAL-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'size' => 'M', 'fabric' => '', 'stock_quantity' => 0]);
        $due = ProductExpectedReceipt::create(['product_id' => $product->id,
            'expected_receive_date' => '2036-01-10', 'expected_receive_quantity' => 3]);
        ProductExpectedReceipt::create(['product_id' => $product->id,
            'expected_receive_date' => '2036-01-11', 'expected_receive_quantity' => 2]);
        $result = app(StockReceiptReminderService::class)->reminders(Carbon::parse('2036-01-10'));
        $this->assertSame([$due->id], $result['manual']->pluck('id')->all());
        $this->assertSame(0, $product->fresh()->stock_quantity);
        $this->assertNull($due->fresh()->received_at);
        $due->delete();
        $this->assertCount(0, app(StockReceiptReminderService::class)->reminders(Carbon::parse('2036-01-10'))['manual']);
    }
}
