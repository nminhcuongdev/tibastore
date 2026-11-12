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

    public function test_legacy_blank_size_does_not_block_stock_updates_to_other_sizes(): void
    {
        $this->actingAs(User::create(['code' => 'blank-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $code = 'BLANK-'.bin2hex(random_bytes(4));
        $blank = Product::create(['code' => $code, 'name' => 'Test', 'size' => '', 'fabric' => '', 'stock_quantity' => 0]);
        $large = Product::create(['code' => $code, 'name' => 'Test', 'size' => 'to', 'fabric' => '', 'stock_quantity' => 5]);
        $data = ['code' => $code, 'name' => 'Test', 'variants' => [
            ['id' => $blank->id, 'size' => '', 'stock_quantity' => 0, 'original_quantity' => 0],
            ['id' => $large->id, 'size' => 'to', 'stock_quantity' => 8, 'original_quantity' => 5],
        ]];
        $this->get('/products/'.$blank->id)->assertOk()->assertSee('Chưa lưu: kiểm tra ô');
        $this->patchJson(route('products.inline-stock', $blank), $data)->assertOk();
        $this->assertSame(8, $large->fresh()->stock_quantity);
        $this->assertSame('', $blank->fresh()->size);
        $this->assertSame(0, $blank->fresh()->stock_quantity);
        $this->assertSame(3, WorkshopStockTransfer::where('product_code', $code)->sole()->lines[0]['quantity']);
        $data['variants'][1]['original_quantity'] = 8;
        $this->patchJson(route('products.inline-stock', $blank), $data)->assertOk();
        $this->assertSame(1, WorkshopStockTransfer::where('product_code', $code)->count());
        $data['variants'][] = ['size' => '', 'stock_quantity' => 2];
        $this->patchJson(route('products.inline-stock', $blank), $data)->assertUnprocessable();
        array_pop($data['variants']);
        $data['variants'][0]['size'] = 'nhỏ';
        $data['variants'][1]['size'] = '';
        $this->patchJson(route('products.inline-stock', $blank), $data)->assertUnprocessable();
        $this->assertSame('', $blank->fresh()->size);
        $this->assertSame('to', $large->fresh()->size);
    }

    public function test_inline_edit_history_includes_old_and_deleted_sizes_but_not_similar_codes(): void
    {
        $this->actingAs(User::create(['code' => 'inline-history-'.bin2hex(random_bytes(4)), 'name' => 'Người sửa',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $code = 'HIST_'.bin2hex(random_bytes(4));
        $product = Product::create(['code' => $code, 'name' => 'Test', 'size' => 'M', 'fabric' => '', 'stock_quantity' => 5]);
        $product->update(['stock_quantity' => 2]);
        $oldChange = $product->changeLogs()->where('event', 'updated')->firstOrFail();
        $deleted = Product::create(['code' => $code, 'name' => 'Test', 'size' => 'SIZE-REMOVED', 'fabric' => '', 'stock_quantity' => 1]);
        $deleted->delete();
        Product::create(['code' => $code.'X', 'name' => 'Test', 'size' => 'UNRELATED-SIZE', 'fabric' => '', 'stock_quantity' => 1]);
        $this->get('/products/'.$product->id)->assertOk()->assertSee('Lịch sử chỉnh sửa')->assertSee('Người sửa')
            ->assertSee('SIZE-REMOVED')->assertDontSee('UNRELATED-SIZE')
            ->assertViewHas('productEditHistory', fn ($history) => $history->contains('id', $oldChange->id));
    }

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
            ->assertSee('Lịch sử chỉnh sửa');
        $this->postJson(route('workshop-orders.undo-stock', $decrease))->assertOk();
        $this->assertSame(3, $product->fresh()->stock_quantity);
        $this->postJson(route('workshop-orders.undo-stock', $decrease))->assertOk();
        $this->assertSame(3, $product->fresh()->stock_quantity);
        $this->postJson(route('workshop-orders.undo-stock', $entry))->assertOk();
        $this->assertSame(3, $product->fresh()->stock_quantity);
    }
}
