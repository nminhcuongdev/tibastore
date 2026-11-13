<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductExpectedReceipt;
use App\Models\User;
use App\Services\OrderStockReviewService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrderStockReviewTest extends TestCase
{
    use DatabaseTransactions;

    private function product(int $stock = 2): Product
    {
        return Product::create(['code' => 'REVIEW-'.bin2hex(random_bytes(4)), 'name' => 'Review product',
            'size' => 'M', 'fabric' => '', 'stock_quantity' => $stock]);
    }

    private function order(Product $product, array $overrides = [], array $lines = []): Order
    {
        $order = Order::create(array_merge(['closer_name' => 'Test', 'order_name' => $product->code,
            'product_id' => $product->id, 'quantity' => 3, 'status' => Order::DEFAULT_STATUS,
            'pickup_date' => '2032-04-05', 'event_date' => '2032-04-06', 'return_date' => '2032-04-07'], $overrides));
        $order->items()->createMany($lines ?: [['product_id' => $product->id, 'quantity' => 3]]);
        return $order;
    }

    public function test_review_groups_duplicate_sizes_ignores_forecasts_and_recalculates_after_stock_fix(): void
    {
        $product = $this->product();
        $order = $this->order($product, [], [
            ['product_id' => $product->id, 'quantity' => 2],
            ['product_id' => $product->id, 'quantity' => 2],
        ]);
        ProductExpectedReceipt::create(['product_id' => $product->id, 'expected_receive_date' => '2032-04-01', 'expected_receive_quantity' => 20]);
        $service = app(OrderStockReviewService::class);
        $review = $service->shortages()->first(fn ($row) => $row['order']->id === $order->id);
        $this->assertCount(1, $review['lines']);
        $this->assertSame(4, $review['lines'][0]['quantity']);
        $this->assertSame(2, $review['lines'][0]['shortage']);
        $this->assertSame(2, $product->fresh()->stock_quantity);
        $product->update(['stock_quantity' => 4]);
        $this->assertNull($service->shortages()->first(fn ($row) => $row['order']->id === $order->id));
    }

    public function test_review_includes_overdue_and_conflicting_orders_but_excludes_closed_and_pending_sizes(): void
    {
        $product = $this->product(4);
        $first = $this->order($product);
        $second = $this->order($product);
        $overdueProduct = $this->product(0);
        $overdue = $this->order($overdueProduct, ['pickup_date' => '2020-01-01', 'event_date' => '2020-01-02', 'return_date' => '2020-01-03']);
        foreach (['huy_don', Order::RETURNED_STATUS, Order::CHECKED_STATUS] as $status) {
            $this->order($product, ['status' => $status]);
        }
        $this->order($product, [], [['product_id' => $product->id, 'quantity' => 100, 'size_pending' => true]]);
        $reviews = app(OrderStockReviewService::class)->shortages()->keyBy(fn ($row) => $row['order']->id);
        $this->assertTrue($reviews->has($overdue->id));
        $this->assertSame(2, $reviews[$first->id]['lines'][0]['shortage']);
        $this->assertSame(2, $reviews[$second->id]['lines'][0]['shortage']);
        $this->assertCount(3, $reviews->filter(fn ($row) => in_array($row['order']->product_id, [$product->id, $overdueProduct->id])));
    }

    public function test_sent_stock_is_not_double_counted_and_page_requires_login(): void
    {
        $this->get(route('orders.stock-review'))->assertRedirect(route('login'));
        $product = $this->product(0);
        $sent = $this->order($product, ['status' => 'da_gui'], [
            ['product_id' => $product->id, 'quantity' => 3, 'stock_held' => 3],
        ]);
        $this->assertNull(app(OrderStockReviewService::class)->shortages()->first(fn ($row) => $row['order']->id === $sent->id));
        $this->order($product);
        $this->actingAs(User::create(['code' => 'review-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'password', 'role' => 'admin', 'delflag' => false]));
        $this->get(route('orders.stock-review'))->assertOk()->assertSee('Đơn thiếu tồn kho')
            ->assertSee($product->code)->assertSee(route('orders.edit', $sent), false);
    }

    public function test_quick_sent_allows_short_stock_keeps_warning_and_only_returns_deducted_stock(): void
    {
        $this->actingAs(User::create(['code' => 'sent-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'password', 'role' => 'admin', 'delflag' => false]));
        $product = $this->product(2);
        $order = $this->order($product);
        // Existing status control still enforces actual stock unless quick sent is explicit.
        $this->patchJson(route('orders.status', $order), ['status' => 'da_gui'])->assertUnprocessable();
        $this->assertSame(Order::DEFAULT_STATUS, $order->fresh()->status);
        $this->patchJson(route('orders.status', $order), ['status' => 'da_gui', 'confirm_sent' => true])
            ->assertOk()->assertJsonPath('order.status', 'da_gui')
            ->assertJsonPath('order.stock_warning', fn ($warning) => str_contains($warning, 'chưa đối chiếu đủ tồn kho'));
        $this->assertSame(0, $product->fresh()->stock_quantity);
        $this->assertSame(2, $order->fresh()->items->first()->stock_held);
        $this->patchJson(route('orders.status', $order), ['status' => 'da_gui', 'confirm_sent' => true])->assertOk();
        $this->assertSame(0, $product->fresh()->stock_quantity);
        $this->patchJson(route('orders.status', $order), ['status' => Order::RETURNED_STATUS])->assertOk();
        $this->assertSame(2, $product->fresh()->stock_quantity);
    }

    public function test_pending_size_is_red_and_can_be_marked_sent_without_editing(): void
    {
        $this->actingAs(User::create(['code' => 'size-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'password', 'role' => 'admin', 'delflag' => false]));
        $product = $this->product(0);
        $order = $this->order($product, [], [['product_id' => $product->id, 'quantity' => 3, 'size_pending' => true]]);
        $this->get(route('orders.index', ['q' => $product->code]))->assertOk()
            ->assertSee('stock-warning-cell')->assertSee('Chưa chốt size')->assertSee('✓ Đã gửi');
        $this->patchJson(route('orders.status', $order), ['status' => 'da_gui', 'confirm_sent' => true])
            ->assertOk()->assertJsonPath('order.stock_warning', 'Chưa chốt size');
        $this->assertSame(0, $product->fresh()->stock_quantity);
    }
}
