<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockImportHistory;
use App\Models\User;
use App\Models\WorkshopOrder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkshopStockTransferTest extends TestCase
{
    use DatabaseTransactions;

    public function test_receipt_filters_distinguish_pending_push_manual_and_unknown_history(): void
    {
        $this->actingAs(User::create(['code' => 'receipt-filter-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $code = 'RECEIPT-'.bin2hex(random_bytes(4));
        $orders = collect();
        foreach ([null, 'pushed', 'manual', null] as $index => $method) {
            $orders->push(WorkshopOrder::create(['product_code' => $code, 'supply_source' => WorkshopOrder::SUPPLY_OUTSIDE,
                'quantity' => $index + 1, 'unit_price' => 1000,
                'status' => $index === 0 ? WorkshopOrder::STATUS_NOT_YET : WorkshopOrder::STATUS_ARRIVED,
                'stocked_at' => $index === 0 ? null : now(), 'stock_receipt_method' => $method]));
        }
        foreach (['pending' => [0], 'received' => [1,2,3], 'pushed' => [1], 'manual' => [2], 'unknown' => [3]] as $filter => $indices) {
            $expected = collect($indices)->map(fn ($index) => $orders[$index]->id)->sort()->values()->all();
            $quantity = collect($indices)->sum(fn ($index) => $orders[$index]->quantity);
            $this->get(route('workshop-orders.index', ['q' => $code, 'supply_source' => WorkshopOrder::SUPPLY_OUTSIDE, 'stock_receipt' => $filter]))
                ->assertOk()->assertSee('Đã tự nhập tay vào kho')
                ->assertViewHas('orders', fn ($rows) => $rows->pluck('id')->sort()->values()->all() === $expected)
                ->assertViewHas('quantityTotal', $quantity)->assertViewHas('amountTotal', $quantity * 1000)
                ->assertViewHas('stockReceipt', $filter);
        }
    }

    public function test_manual_stock_acknowledgement_removes_forecast_without_adding_stock(): void
    {
        $this->actingAs(User::create(['code' => 'manual-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $product = Product::create(['code' => 'MANUAL-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'size' => 'M', 'fabric' => '', 'stock_quantity' => 9]);
        $item = WorkshopOrder::create(['product_code' => $product->code, 'supply_source' => WorkshopOrder::SUPPLY_OUTSIDE,
            'quantity' => 7, 'status' => WorkshopOrder::STATUS_ORDERED, 'returned_date' => '2034-06-01',
            'size_quantities' => [['product_id' => $product->id, 'quantity' => 7]]]);
        $receipt = \App\Models\ProductExpectedReceipt::create(['product_id' => $product->id,
            'workshop_order_id' => $item->id, 'expected_receive_date' => '2034-06-01', 'expected_receive_quantity' => 7]);
        $inventory = app(\App\Services\OrderInventoryService::class);
        $available = fn () => $inventory->projectedAvailableQuantities([$product->id], '2034-06-02', '2034-06-04')[$product->id];
        $this->assertSame(16, $available());
        $this->get('/workshop-orders?supply_source=mua_ngoai')->assertOk()->assertSee('Tự nhập tay vào kho');
        $url = route('workshop-orders.manual-stock', $item);
        $this->postJson($url)->assertOk();
        $this->assertSame(9, $available());
        $this->assertSame(9, $product->fresh()->stock_quantity);
        $this->assertNotNull($receipt->fresh()->received_at);
        $this->assertNotNull($item->fresh()->stocked_at);
        $this->assertSame('manual', $item->fresh()->stock_receipt_method);
        $this->assertSame(WorkshopOrder::STATUS_ARRIVED, $item->fresh()->status);
        $this->postJson($url)->assertOk();
        $this->postJson(route('workshop-orders.stock', $item))->assertOk();
        $this->assertSame('manual', $item->fresh()->stock_receipt_method);
        $this->assertSame(9, $product->fresh()->stock_quantity);
        $this->assertSame(0, StockImportHistory::where('product_id', $product->id)->count());
        $entry = \App\Models\WorkshopStockTransfer::where('workshop_order_id', $item->id)->sole();
        $this->assertFalse((bool) $entry->can_undo);
        $this->postJson(route('workshop-orders.undo-stock', $entry))->assertUnprocessable();
        $this->assertSame(9, $product->fresh()->stock_quantity);
    }

    public function test_stock_transfer_syncs_image_to_all_sizes_and_retry_only_updates_image(): void
    {
        $this->actingAs(User::create(['code' => 'img-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $code = 'IMG-'.bin2hex(random_bytes(4));
        $m = Product::create(['code' => $code, 'name' => 'Test', 'size' => 'M', 'fabric' => '', 'stock_quantity' => 0]);
        $l = Product::create(['code' => $code, 'name' => 'Test', 'size' => 'L', 'fabric' => '', 'stock_quantity' => 0,
            'image_path' => 'products/old.jpg']);
        $item = WorkshopOrder::create(['product_code' => $code, 'supply_source' => WorkshopOrder::SUPPLY_OUTSIDE,
            'quantity' => 2, 'status' => WorkshopOrder::STATUS_NOT_YET, 'image_path' => 'workshop-orders/flower.jpg',
            'size_quantities' => [['product_id' => $m->id, 'quantity' => 2]]]);
        $url = route('workshop-orders.stock', $item);
        $this->postJson($url)->assertOk();
        $this->assertSame('workshop-orders/flower.jpg', $m->fresh()->image_path);
        $this->assertSame('workshop-orders/flower.jpg', $l->fresh()->image_path);
        $this->assertSame(2, $m->fresh()->stock_quantity);
        $this->assertSame(0, $l->fresh()->stock_quantity);
        $item->update(['image_path' => 'workshop-orders/new-flower.jpg']);
        $this->postJson($url)->assertOk();
        $this->assertSame('workshop-orders/new-flower.jpg', $m->fresh()->image_path);
        $this->assertSame('workshop-orders/new-flower.jpg', $l->fresh()->image_path);
        $this->assertSame(2, $m->fresh()->stock_quantity);
        $this->assertSame(1, StockImportHistory::where('product_id', $m->id)->count());
        $this->get('/workshop-orders?supply_source=mua_ngoai')->assertOk()->assertSee('Cập nhật ảnh lên kho');
    }

    public function test_size_quantities_are_saved_and_stock_transfer_is_idempotent(): void
    {
        $this->actingAs(User::create(['code' => 'push-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $code = 'PUSH-'.bin2hex(random_bytes(4));
        $m = Product::create(['code' => $code, 'name' => 'Test', 'size' => 'M', 'fabric' => '', 'stock_quantity' => 10]);
        $l = Product::create(['code' => $code, 'name' => 'Test', 'size' => 'L', 'fabric' => '', 'stock_quantity' => 5]);
        $data = ['product_code' => $code, 'supply_source' => WorkshopOrder::SUPPLY_OUTSIDE,
            'quantity' => 1, 'unit_price' => 1000, 'status' => WorkshopOrder::STATUS_NOT_YET,
            'size_quantities' => json_encode([['product_id' => $m->id, 'quantity' => 2], ['product_id' => $l->id, 'quantity' => 3]])];
        $this->postJson('/workshop-orders', $data)->assertCreated()->assertJsonPath('quantity', 5)->assertJsonStructure(['stock_url']);
        $od = WorkshopOrder::where('product_code', $code)->firstOrFail();
        $this->assertSame('M: 2, L: 3', $od->size_note);
        $this->get('/workshop-orders?supply_source=mua_ngoai')->assertOk()->assertSee('Đẩy lên kho')->assertSee('data-stock-cell', false);
        $this->postJson('/workshop-orders/'.$od->id.'/stock')->assertOk();
        $this->assertSame(12, $m->fresh()->stock_quantity);
        $this->assertSame(8, $l->fresh()->stock_quantity);
        $this->assertNotNull($od->fresh()->stocked_at);
        $this->assertSame('pushed', $od->fresh()->stock_receipt_method);
        $this->assertSame(WorkshopOrder::STATUS_ARRIVED, $od->fresh()->status);
        $this->postJson('/workshop-orders/'.$od->id.'/stock')->assertOk();
        $this->assertSame(12, $m->fresh()->stock_quantity);
        $this->assertSame(2, StockImportHistory::whereIn('product_id', [$m->id, $l->id])->count());
        $data['size_quantities'] = json_encode([['product_id' => $m->id, 'quantity' => 4]]);
        $this->putJson('/workshop-orders/'.$od->id, $data)->assertUnprocessable();
        $entry = \App\Models\WorkshopStockTransfer::where('workshop_order_id', $od->id)->firstOrFail();
        $this->get('/products/'.$m->id)->assertOk()->assertSee('Lịch sử mua')->assertSee('Hoàn tác')
            ->assertSee(route('workshop-orders.undo-stock', $entry));
        $this->postJson(route('workshop-orders.undo-stock', $entry))->assertOk();
        $this->assertSame(10, $m->fresh()->stock_quantity);
        $this->assertSame(5, $l->fresh()->stock_quantity);
        $this->assertNull($od->fresh()->stocked_at);
        $this->assertNull($od->fresh()->stock_receipt_method);
        $this->assertNotNull($entry->fresh()->undone_at);
        $this->postJson(route('workshop-orders.undo-stock', $entry))->assertOk();
        $this->assertSame(10, $m->fresh()->stock_quantity);
        $this->postJson('/workshop-orders/'.$od->id.'/stock')->assertOk();
        $this->assertSame(12, $m->fresh()->stock_quantity);
        $this->assertSame(2, \App\Models\WorkshopStockTransfer::where('workshop_order_id', $od->id)->count());
        $latest = \App\Models\WorkshopStockTransfer::where('workshop_order_id', $od->id)->latest('id')->firstOrFail();
        $l->update(['stock_quantity' => 0]);
        $this->postJson(route('workshop-orders.undo-stock', $latest))->assertUnprocessable()->assertJsonValidationErrors('undo');
        $this->assertSame(12, $m->fresh()->stock_quantity);
        $this->assertNull($latest->fresh()->undone_at);
        $this->get('/products/'.$m->id)->assertOk()->assertSee('Đã hoàn tác');
        $this->get('/products/'.$m->id)->assertOk()
            ->assertSee(route('workshop-orders.delete-stock-history', $entry))
            ->assertDontSee('action="'.route('workshop-orders.delete-stock-history', $latest).'"', false);
        $this->deleteJson(route('workshop-orders.delete-stock-history', $latest))->assertUnprocessable();
        $this->assertNotNull($latest->fresh());
        $this->deleteJson(route('workshop-orders.delete-stock-history', $entry))->assertOk();
        $this->assertNull($entry->fresh());
        $this->assertSame(12, $m->fresh()->stock_quantity);
        $this->assertSame(0, $l->fresh()->stock_quantity);
        $this->assertNotNull($od->fresh()->stocked_at);
        $empty = WorkshopOrder::create(['product_code' => WorkshopOrder::NO_PRODUCT_CODE, 'supply_source' => 'mua_ngoai',
            'quantity' => 1, 'status' => WorkshopOrder::STATUS_NOT_YET]);
        $this->postJson('/workshop-orders/'.$empty->id.'/stock')->assertUnprocessable()->assertJsonValidationErrors('size_quantities');
        $this->assertNull($empty->fresh()->stocked_at);
    }
}
