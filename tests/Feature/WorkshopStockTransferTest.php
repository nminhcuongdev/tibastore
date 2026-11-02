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
        $this->assertSame(WorkshopOrder::STATUS_ARRIVED, $od->fresh()->status);
        $this->postJson('/workshop-orders/'.$od->id.'/stock')->assertOk();
        $this->assertSame(12, $m->fresh()->stock_quantity);
        $this->assertSame(2, StockImportHistory::whereIn('product_id', [$m->id, $l->id])->count());
        $data['size_quantities'] = json_encode([['product_id' => $m->id, 'quantity' => 4]]);
        $this->putJson('/workshop-orders/'.$od->id, $data)->assertUnprocessable();
        $empty = WorkshopOrder::create(['product_code' => WorkshopOrder::NO_PRODUCT_CODE, 'supply_source' => 'mua_ngoai',
            'quantity' => 1, 'status' => WorkshopOrder::STATUS_NOT_YET]);
        $this->postJson('/workshop-orders/'.$empty->id.'/stock')->assertUnprocessable()->assertJsonValidationErrors('size_quantities');
        $this->assertNull($empty->fresh()->stocked_at);
    }
}
