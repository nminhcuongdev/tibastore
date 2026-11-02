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

class WorkshopArrivalReminderTest extends TestCase
{
    use DatabaseTransactions;

    public function test_reminders_match_customer_and_product_with_two_day_boundary_and_stop_after_arrival(): void
    {
        $name = 'Reminder-'.bin2hex(random_bytes(5));
        $product = Product::create(['code' => $name, 'name' => 'Áo thử', 'size' => 'M', 'fabric' => '', 'stock_quantity' => 10]);
        $booking = function ($suffix, $pickup, $status = 'chua_cho_size') use ($name, $product) {
            $order = Order::create(['order_name' => $name.$suffix, 'closer_name' => 'Test', 'product_id' => $product->id,
                'quantity' => 1, 'pickup_date' => $pickup, 'event_date' => $pickup, 'return_date' => '2031-01-20', 'status' => $status]);
            $order->items()->create(['product_id' => $product->id, 'quantity' => 1]);
            return $order;
        };
        $supply = function ($suffix, $source, $status = WorkshopOrder::STATUS_ORDERED, $code = null) use ($name, $product) {
            return WorkshopOrder::create(['order_name' => $name.$suffix, 'product_code' => $code ?? $product->code,
                'supply_source' => $source, 'quantity' => 1, 'unit_price' => 0, 'status' => $status]);
        };
        $due = $booking('-due', '2031-01-12');
        $workshop = $supply('-due', WorkshopOrder::SUPPLY_WORKSHOP);
        $outside = $supply('-due', WorkshopOrder::SUPPLY_OUTSIDE, WorkshopOrder::STATUS_NOT_YET);
        $supply('-due', WorkshopOrder::SUPPLY_OUTSIDE, WorkshopOrder::STATUS_ARRIVED);
        $supply('-due', WorkshopOrder::SUPPLY_OUTSIDE, WorkshopOrder::STATUS_ORDERED, 'WRONG-CODE');
        $supply('-other-name', WorkshopOrder::SUPPLY_OUTSIDE);
        $booking('-later', '2031-01-13');
        $supply('-later', WorkshopOrder::SUPPLY_WORKSHOP);
        $booking('-sent', '2031-01-11', 'da_gui');
        $supply('-sent', WorkshopOrder::SUPPLY_OUTSIDE);
        $booking('-cancel', '2031-01-11', 'huy_don');
        $supply('-cancel', WorkshopOrder::SUPPLY_OUTSIDE);
        $booking('-late', '2031-01-09');
        $late = $supply('-late', WorkshopOrder::SUPPLY_OUTSIDE);
        $booking('-uncoded', '2031-01-10');
        $uncoded = $supply('-uncoded', WorkshopOrder::SUPPLY_OUTSIDE, WorkshopOrder::STATUS_ORDERED, WorkshopOrder::NO_PRODUCT_CODE);
        $service = app(WorkshopArrivalReminderService::class);
        $today = Carbon::parse('2031-01-10', 'Asia/Ho_Chi_Minh');
        $mine = fn () => $service->reminders($today)->filter(fn ($row) => str_starts_with($row['order']->order_name, $name));
        $this->assertEqualsCanonicalizing([$workshop->id, $outside->id, $late->id, $uncoded->id], $mine()->pluck('item.id')->all());
        $this->assertTrue($mine()->firstWhere('item.id', $late->id)['overdue']);
        $workshop->update(['status' => WorkshopOrder::STATUS_ARRIVED]);
        $this->assertNotContains($workshop->id, $mine()->pluck('item.id')->all());

        $this->actingAs(User::create(['code' => 'rem-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $this->travelTo($today);
        try {
            $this->get('/orders?q=NO-MATCH')->assertOk()->assertSee('Nhắc hàng chưa về')
                ->assertSee('arrival-reminder-popup', false)->assertSee($name.'-due')->assertSee('Khách lấy: 12/01/2031')
                ->assertSee(route('workshop-orders.index', ['focus' => $outside->id]).'#workshop-row-'.$outside->id)
                ->assertSee(route('orders.show', $due));
            $this->get('/workshop-orders?focus='.$outside->id)->assertOk()
                ->assertSee('id="workshop-row-'.$outside->id.'"', false)
                ->assertDontSee('id="workshop-row-'.$workshop->id.'"', false)
                ->assertSee('Xem tất cả đặt hàng')->assertViewHas('supplySource', WorkshopOrder::SUPPLY_OUTSIDE);
        } finally {
            $this->travelBack();
        }
    }
}
