<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Tests\TestCase;

class ExpandedOrderGroupingTest extends TestCase
{
    public function test_same_code_shares_one_row_with_size_quantities_and_correct_total(): void
    {
        $order = new Order(['order_name' => 'Test', 'total_amount' => 1490000]);
        $items = collect();
        foreach ([['XL', 5, 110000, 1], ['XXL', 5, 110000, 1], ['XL', 1, 130000, 3]] as [$size, $quantity, $price, $days]) {
            $item = new OrderItem(['quantity' => $quantity, 'rental_price' => $price, 'rental_days' => $days]);
            $item->setRelation('product', new Product(['code' => 'CTN11', 'name' => 'Cổ phục', 'size' => $size,
                'image_path' => $size === 'XXL' ? 'products/group.jpg' : null]));
            $items->push($item);
        }
        $order->setRelation('items', $items);
        $html = view('orders.expanded-invoice', ['order' => $order, 'codeSummary' => $order->codeSummary()])->render();
        $this->assertSame(1, substr_count($html, 'class="order-invoice-code"'));
        $this->assertStringContainsString('XL: 6', $html);
        $this->assertStringContainsString('XXL: 5', $html);
        $this->assertStringContainsString('1.490.000', $html);
        $this->assertStringContainsString('products/group.jpg', $html);
        $this->assertStringContainsString('130.000', $html);
    }
}
