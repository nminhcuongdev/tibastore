<?php

namespace Tests\Unit;

use App\Models\DraftInvoice;
use PHPUnit\Framework\TestCase;

class DraftInvoiceDaysTest extends TestCase
{
    public function test_fractional_days_and_legacy_items_are_included_before_discount(): void
    {
        $invoice = new DraftInvoice(['items' => [
            ['quantity' => 6, 'unit_price' => 90000, 'days' => '1,5'],
            ['quantity' => 4, 'unit_price' => 90000, 'days' => 1.6],
            ['quantity' => 1, 'unit_price' => 10000],
        ], 'discount_type' => 'percent', 'discount_value' => 10]);

        $this->assertEquals(1396000, $invoice->subtotal);
        $this->assertEquals(139600, $invoice->discount_amount);
        $this->assertEquals(1256400, $invoice->total);
    }
}
