<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductExpectedReceipt;
use App\Models\WorkshopOrder;
use App\Models\WorkshopStockTransfer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LegacyManualStockTest extends TestCase
{
    use DatabaseTransactions;

    public function test_only_arrived_pre_november_orders_are_acknowledged_without_adding_stock(): void
    {
        $code = 'LEGACY-'.bin2hex(random_bytes(4));
        $product = Product::create(['code' => $code, 'name' => 'Test', 'size' => 'M', 'fabric' => '', 'stock_quantity' => 5]);
        $base = ['product_code' => $code, 'quantity' => 7, 'supply_source' => WorkshopOrder::SUPPLY_OUTSIDE,
            'status' => WorkshopOrder::STATUS_ARRIVED, 'created_at' => '2026-10-01 00:00:00'];
        $arrived = WorkshopOrder::create($base + ['returned_date' => '2026-10-31']);
        $ordered = WorkshopOrder::create($base + ['ordered_date' => '2026-10-15']);
        $undated = WorkshopOrder::create($base);
        $undated->forceFill(['created_at' => '2026-10-01 00:00:00'])->save();
        $november = WorkshopOrder::create($base + ['returned_date' => '2026-11-01']);
        $pending = WorkshopOrder::create(array_replace($base, ['returned_date' => '2026-10-01', 'status' => WorkshopOrder::STATUS_NOT_YET]));
        $pushed = WorkshopOrder::create($base + ['returned_date' => '2026-10-01', 'stocked_at' => now(), 'stock_receipt_method' => 'pushed']);
        $receipt = ProductExpectedReceipt::create(['product_id' => $product->id, 'workshop_order_id' => $arrived->id,
            'expected_receive_date' => '2026-10-31', 'expected_receive_quantity' => 7]);
        $migration = require database_path('migrations/2026_11_13_000000_acknowledge_pre_november_manual_stock.php');
        $migration->up();
        foreach ([$arrived, $ordered, $undated] as $row) {
            $this->assertSame('manual', $row->fresh()->stock_receipt_method);
            $this->assertNotNull($row->fresh()->stocked_at);
        }
        $this->assertNull($november->fresh()->stocked_at);
        $this->assertNull($pending->fresh()->stocked_at);
        $this->assertSame('pushed', $pushed->fresh()->stock_receipt_method);
        $this->assertNotNull($receipt->fresh()->received_at);
        $this->assertSame(5, $product->fresh()->stock_quantity);
        $count = WorkshopStockTransfer::whereIn('workshop_order_id', [$arrived->id, $ordered->id, $undated->id])->count();
        $this->assertSame(3, $count);
        $migration->up();
        $this->assertSame($count, WorkshopStockTransfer::whereIn('workshop_order_id', [$arrived->id, $ordered->id, $undated->id])->count());
        $this->assertSame(5, $product->fresh()->stock_quantity);
    }
}
