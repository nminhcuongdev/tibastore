<?php

namespace Tests\Unit;

use App\Models\Order;
use PHPUnit\Framework\TestCase;

class CancelledOrderTotalTest extends TestCase
{
    public function test_cancellation_zeroes_total_without_losing_original_amounts(): void
    {
        $order = new Order([
            'status' => 'da_soan_xong',
            'total_amount' => 500000,
            'compensation_amount' => 100000,
        ]);

        $this->assertSame(600000, $order->total_with_compensation);

        $order->status = 'huy_don';
        $this->assertSame(0, $order->total_with_compensation);

        $order->status = 'da_soan_xong';
        $this->assertSame(600000, $order->total_with_compensation);
    }
}
