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
            'return_period' => 'sang',
            'source' => Order::DEFAULT_SOURCE, 'status' => Order::DEFAULT_STATUS,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'rental_price' => 100000]]];
        $this->post('/orders', $data)->assertSessionHasNoErrors()->assertRedirect();
        $order = Order::where('product_id', $product->id)->firstOrFail();
        $this->assertSame('sang', $order->return_period);
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
    }
}
