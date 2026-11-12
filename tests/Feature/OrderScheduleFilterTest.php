<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrderScheduleFilterTest extends TestCase
{
    use DatabaseTransactions;

    public function test_schedule_filters_use_their_own_dates_and_include_range_boundaries(): void
    {
        $this->actingAs(User::create(['code' => 'filter-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $code = 'FILTER-'.bin2hex(random_bytes(4));
        $product = Product::create(['code' => $code, 'name' => 'Test', 'size' => 'M', 'fabric' => '', 'stock_quantity' => 20]);
        $orders = collect();
        foreach ([['2034-06-01', '2034-06-10'], ['2034-06-02', '2034-06-11'], ['2034-06-03', '2034-06-12']] as [$dispatch, $actualReturn]) {
            $orders->push(Order::create(['product_id' => $product->id, 'quantity' => 1, 'order_name' => $code,
                'closer_name' => 'Test', 'status' => Order::DEFAULT_STATUS,
                'dispatch_date' => $dispatch, 'actual_return_date' => $actualReturn,
                'pickup_date' => '2034-06-04', 'event_date' => '2034-06-05', 'return_date' => '2034-06-06',
                'total_amount' => 10000]));
        }
        $cases = [
            [['dispatch_from' => '2034-06-02'], [1, 2]],
            [['dispatch_to' => '2034-06-02'], [0, 1]],
            [['actual_return_from' => '2034-06-11'], [1, 2]],
            [['actual_return_to' => '2034-06-11'], [0, 1]],
            [['dispatch_from' => '2034-06-01', 'dispatch_to' => '2034-06-02',
                'actual_return_from' => '2034-06-11', 'actual_return_to' => '2034-06-12',
                'pickup_from' => '2034-06-04', 'pickup_to' => '2034-06-04'], [1]],
        ];
        foreach ($cases as [$filters, $indices]) {
            $expected = collect($indices)->map(fn ($index) => $orders[$index]->id)->sort()->values()->all();
            $this->get(route('orders.index', ['q' => $code] + $filters))->assertOk()
                ->assertSee('Ngày đi đơn từ')->assertSee('Ngày trả về TT đến')
                ->assertViewHas('orders', fn ($rows) => $rows->pluck('id')->sort()->values()->all() === $expected)
                ->assertViewHas('orderTotal', count($expected) * 10000)
                ->assertViewHas('filters', fn ($values) => collect($filters)->every(fn ($value, $key) => $values[$key] === $value));
        }
    }
}
