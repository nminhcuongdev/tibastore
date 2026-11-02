<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\WorkshopStockTransfer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DirectStockHistoryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_direct_initial_stock_and_increases_appear_in_purchase_history_without_duplicate_entries(): void
    {
        $user = User::create(['code' => 'direct-'.bin2hex(random_bytes(4)), 'name' => 'Người nhập thử',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]);
        $this->actingAs($user);
        $code = 'DIRECT-'.bin2hex(random_bytes(4));
        $this->post('/products', ['code' => $code, 'name' => 'Áo thử',
            'variants' => [['size' => 'M', 'stock_quantity' => 3]]])->assertSessionHasNoErrors()->assertRedirect();
        $product = Product::where('code', $code)->firstOrFail();
        $this->assertSame(1, WorkshopStockTransfer::where('product_code', $code)->count());
        $data = ['code' => $code, 'name' => 'Áo thử', 'size' => 'M', 'stock_quantity' => 8, 'apply_to_all_sizes' => 1];
        $this->put('/products/'.$product->id, $data)->assertSessionHasNoErrors()->assertRedirect();
        $entry = WorkshopStockTransfer::where('product_code', $code)->latest('id')->firstOrFail();
        $this->assertNull($entry->workshop_order_id);
        $this->assertSame('direct_stock', $entry->supply_source);
        $this->assertSame($user->id, $entry->user_id);
        $this->assertSame(5, $entry->lines[0]['quantity']);
        $this->assertSame(3, $entry->lines[0]['previous_quantity']);
        $this->assertSame(8, $entry->lines[0]['new_quantity']);
        $this->put('/products/'.$product->id, $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(2, WorkshopStockTransfer::where('product_code', $code)->count());
        $this->get('/products/'.$product->id)->assertOk()->assertSee('Nhập trực tiếp tại Kho')->assertSee('Người nhập thử')
            ->assertSee('+5')->assertSee(route('workshop-orders.undo-stock', $entry));
        $this->postJson(route('workshop-orders.undo-stock', $entry))->assertOk();
        $this->assertSame(3, $product->fresh()->stock_quantity);
        $data['stock_quantity'] = 1;
        $this->put('/products/'.$product->id, $data)->assertSessionHasNoErrors()->assertRedirect();
        $decrease = WorkshopStockTransfer::where('product_code', $code)->latest('id')->firstOrFail();
        $this->assertSame(-2, $decrease->lines[0]['quantity']);
        $this->get('/products/'.$product->id)->assertOk()->assertSee('-2')->assertSee('3 → 1')
            ->assertSee('Xem lịch sử thay đổi trước đây');
        $this->postJson(route('workshop-orders.undo-stock', $decrease))->assertOk();
        $this->assertSame(3, $product->fresh()->stock_quantity);
        $this->postJson(route('workshop-orders.undo-stock', $decrease))->assertOk();
        $this->assertSame(3, $product->fresh()->stock_quantity);
        $this->postJson(route('workshop-orders.undo-stock', $entry))->assertOk();
        $this->assertSame(3, $product->fresh()->stock_quantity);
    }
}
