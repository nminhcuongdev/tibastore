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

    public function test_discount_reduces_goods_total_and_remaining_but_not_compensation(): void
    {
        $order = new Order(['status' => 'da_soan_xong', 'total_amount' => 500000,
            'discount_amount' => 100000, 'compensation_amount' => 50000,
            'shipping_fee' => 30000, 'payment_1' => 200000, 'payment_2' => 100000]);
        $this->assertSame(400000, $order->net_total);
        $this->assertSame(450000, $order->total_with_compensation);
        $this->assertSame(180000, $order->remaining);
        $order->status = 'huy_don';
        $this->assertSame(0, $order->total_with_compensation);
    }
}
