<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrderDayPeriodTest extends TestCase
{
    use DatabaseTransactions;

    public function test_periods_are_saved_editable_displayed_and_validated(): void
    {
        $this->actingAs(User::create(['code' => 'period-'.bin2hex(random_bytes(5)),
            'name' => 'Test', 'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $product = Product::create(['code' => 'PER-'.bin2hex(random_bytes(5)), 'name' => 'Áo thử',
            'fabric' => 'Lụa', 'size' => 'M', 'stock_quantity' => 10]);
        $data = ['closer_name' => 'Test', 'order_name' => 'Buổi diễn thử',
            'pickup_date' => '2031-10-23', 'event_date' => '2031-10-24', 'return_date' => '2031-10-25',
            'return_period' => 'sang', 'discount_amount' => 10000,
            'source' => Order::DEFAULT_SOURCE, 'status' => Order::DEFAULT_STATUS,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'rental_price' => 100000]]];
        $this->post('/orders', $data)->assertSessionHasNoErrors()->assertRedirect();
        $order = Order::where('product_id', $product->id)->firstOrFail();
        $this->assertSame('sang', $order->return_period);
        $this->assertSame(10000, $order->discount_amount);
        $this->assertSame(90000, $order->total_with_compensation);
        $this->assertSame(90000, $order->remaining);
        $this->get('/orders/'.$order->id)->assertOk()->assertSee('24/10/2031')->assertSee('25/10/2031 Sáng');
        $this->get('/orders/'.$order->id.'/edit')->assertOk()->assertDontSee('name="event_period"', false)->assertSee('Buổi trả');
        $data['return_period'] = 'chieu';
        $this->put('/orders/'.$order->id, $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('chieu', $order->fresh()->return_period);
        $data['return_period'] = 'invalid';
        $this->postJson('/orders', $data)->assertUnprocessable()->assertJsonValidationErrors('return_period');
        unset($data['return_period']);
        $this->put('/orders/'.$order->id, $data)->assertSessionHasNoErrors();
        $this->assertNull($order->fresh()->return_period);
        $data['discount_amount'] = 100001;
        $this->postJson('/orders', $data)->assertUnprocessable()->assertJsonValidationErrors('discount_amount');
        $data['discount_amount'] = -1;
        $this->postJson('/orders', $data)->assertUnprocessable()->assertJsonValidationErrors('discount_amount');
        $data['discount_type'] = 'percent';
        $data['discount_amount'] = 12.5;
        $this->put('/orders/'.$order->id, $data)->assertSessionHasNoErrors();
        $this->assertSame('12.50', $order->fresh()->discount_percent);
        $this->assertSame(12500, $order->fresh()->discount_amount);
        $this->assertSame(87500, $order->fresh()->remaining);
        $data['items'][0]['quantity'] = 2;
        $this->put('/orders/'.$order->id, $data)->assertSessionHasNoErrors();
        $this->assertSame(25000, $order->fresh()->discount_amount);
        $this->assertSame(175000, $order->fresh()->remaining);
        $data['discount_amount'] = 101;
        $this->postJson('/orders', $data)->assertUnprocessable()->assertJsonValidationErrors('discount_amount');
        $data['discount_type'] = 'vnd';
        $data['discount_amount'] = 5000;
        $this->put('/orders/'.$order->id, $data)->assertSessionHasNoErrors();
        $this->assertNull($order->fresh()->discount_percent);
        $this->assertSame(5000, $order->fresh()->discount_amount);
        $data['items'][0]['rental_days'] = 3;
        $this->put('/orders/'.$order->id, $data)->assertSessionHasNoErrors();
        $this->assertSame('3.00', $order->fresh()->items->first()->rental_days);
        $this->assertSame(600000, $order->fresh()->total_amount);
        $this->assertSame(595000, $order->fresh()->remaining);
        $this->get('/orders/'.$order->id)->assertOk()->assertSee('Số ngày')->assertSee('600,000 VND');
        $this->get('/orders/'.$order->id.'/edit')->assertOk()->assertSee('data-block-days', false);
        $data['items'][0]['rental_days'] = 0;
        $this->postJson('/orders', $data)->assertUnprocessable()->assertJsonValidationErrors('items.0.rental_days');
        $data['items'][0]['rental_days'] = 1.5;
        $this->put('/orders/'.$order->id, $data)->assertSessionHasNoErrors();
        $this->assertSame('1.50', $order->fresh()->items->first()->rental_days);
        $this->assertSame(300000, $order->fresh()->total_amount);
        $data['items'][0]['rental_days'] = '2,5';
        $this->put('/orders/'.$order->id, $data)->assertSessionHasNoErrors();
        $this->assertSame('2.50', $order->fresh()->items->first()->rental_days);
        $this->assertSame(500000, $order->fresh()->total_amount);
        $this->get('/orders/'.$order->id)->assertOk()->assertSee('2,5');
        $data['items'][0]['rental_days'] = '1.555';
        $this->postJson('/orders', $data)->assertUnprocessable()->assertJsonValidationErrors('items.0.rental_days');
    }
}
