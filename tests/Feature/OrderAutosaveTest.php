<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderAutosaveTest extends TestCase
{
    use DatabaseTransactions;

    public function test_autosave_creates_once_updates_and_preserves_saved_data_on_validation_failure(): void
    {
        $this->actingAs(User::create(['code' => 'auto-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $product = Product::create(['code' => 'AUTO-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'fabric' => '', 'size' => 'M', 'stock_quantity' => 20]);
        $data = ['_autosave_token' => (string) Str::uuid(), 'closer_name' => 'Test', 'order_name' => $product->code,
            'pickup_date' => '2032-03-03', 'event_date' => '2032-03-04', 'return_date' => '2032-03-05',
            'source' => Order::DEFAULT_SOURCE, 'status' => Order::DEFAULT_STATUS,
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'rental_price' => 10000]]];
        $id = $this->postJson('/orders', $data)->assertCreated()->assertJsonStructure(['id', 'update_url', 'edit_url', 'show_url'])->json('id');
        $data['items'][0]['quantity'] = 3;
        $this->postJson('/orders', $data)->assertOk()->assertJsonPath('id', $id);
        $this->assertSame(1, Order::where('order_name', $product->code)->count());
        $this->assertSame(3, Order::findOrFail($id)->items->sum('quantity'));
        $invalid = $data; $invalid['order_name'] = '';
        $this->putJson('/orders/'.$id, $invalid)->assertUnprocessable()->assertJsonValidationErrors('order_name');
        $this->assertSame($product->code, Order::findOrFail($id)->order_name);
        $data['note'] = 'Đã sửa sau tự lưu';
        $this->putJson('/orders/'.$id, $data)->assertOk()->assertJsonPath('id', $id);
        // Clicking the regular save button after an uncertain first response also reuses the order.
        $this->post('/orders', $data)->assertSessionHasNoErrors()->assertRedirect(route('orders.show', $id));
        $this->assertSame(1, Order::where('order_name', $product->code)->count());
        $this->assertSame('Đã sửa sau tự lưu', Order::findOrFail($id)->note);
        $this->assertSame(20, $product->fresh()->stock_quantity);
        $itemId = Order::findOrFail($id)->items->first()->id;
        $this->patchJson(route('orders.note', $id), ['note' => 'Ghi chú tại bảng'])->assertOk()->assertJsonPath('note', 'Ghi chú tại bảng');
        $this->assertSame($itemId, Order::findOrFail($id)->items->first()->id);
        $this->assertSame(20, $product->fresh()->stock_quantity);
        $this->patchJson(route('orders.note', $id), ['note' => ''])->assertOk()->assertJsonPath('note', null);
        $this->patchJson(route('orders.note', $id), ['note' => str_repeat('a', 2001)])->assertUnprocessable();
    }

    public function test_incomplete_autosave_does_not_create_an_order(): void
    {
        $this->actingAs(User::create(['code' => 'partial-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $token = (string) Str::uuid();
        $this->postJson('/orders', ['_autosave_token' => $token, 'order_name' => 'Chưa đủ thông tin'])
            ->assertUnprocessable()->assertJsonValidationErrors(['pickup_date', 'items']);
        $this->assertFalse(Order::where('autosave_key', hash('sha256', auth()->id().':'.$token))->exists());
        $this->get('/orders/create')->assertOk()->assertSee('Tự động lưu đã bật.')->assertSee('autosave_token');
    }
}
