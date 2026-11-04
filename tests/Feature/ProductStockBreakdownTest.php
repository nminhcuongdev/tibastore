<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductStockBreakdownTest extends TestCase
{
    use DatabaseTransactions;

    public function test_create_product_with_image_and_schedule_opens_created_product_without_adding_expected_stock(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $this->actingAs(User::create(['code' => 'image-create-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $code = 'PICCREATE-'.bin2hex(random_bytes(4));
        $image = \Illuminate\Http\UploadedFile::fake()->createWithContent('clipboard.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
        $this->post('/products', ['code' => $code, 'name' => 'Ảnh mới', 'image' => $image, 'arrival_dates' => ['2032-02-01'],
            'variants' => [['size' => 'S', 'stock_quantity' => 0, 'expected_receipts' => [
                ['expected_receive_date' => '2032-01-01', 'expected_receive_quantity' => 3]]]]])
            ->assertSessionHasNoErrors()->assertRedirect();
        $product = Product::where('code', $code)->firstOrFail();
        $this->assertSame(0, $product->stock_quantity);
        $this->assertSame(['2032-02-01'], $product->arrival_dates);
        $this->assertSame(3, $product->expectedReceipts->first()->expected_receive_quantity);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($product->image_path);
        $this->get(route('products.show', $product))->assertOk()->assertSee(asset('storage/'.$product->image_path));
        $this->get('/products/create')->assertOk()->assertSee('Ctrl+V')->assertSee('Dự kiến hàng về')->assertSee('data-create-arrivals', false)->assertSee('product_image_preview');
    }

    public function test_deleting_od_removes_its_schedule_and_pending_receipt(): void
    {
        $this->actingAs(User::create(['code' => 'delete-schedule-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $product = Product::create(['code' => 'DELSCHED-'.bin2hex(random_bytes(4)), 'name' => 'Test', 'fabric' => '', 'size' => 'M', 'stock_quantity' => 2]);
        $od = \App\Models\WorkshopOrder::create(['product_code' => $product->code, 'supply_source' => 'mua_ngoai',
            'returned_date' => '2026-11-06', 'quantity' => 1, 'status' => 'da_od', 'size_note' => 'M: 1']);
        $receipt = $product->expectedReceipts()->create(['workshop_order_id' => $od->id, 'expected_receive_date' => '2026-11-06', 'expected_receive_quantity' => 1]);
        $url = route('products.incoming-schedule', $product);
        $this->get($url)->assertOk()->assertSee('data-incoming-workshop="'.$od->id.'"', false);
        $this->delete(route('workshop-orders.destroy', $od))->assertRedirect();
        $this->assertNull($receipt->fresh());
        $this->get($url)->assertOk()->assertDontSee('data-incoming-workshop="'.$od->id.'"', false)->assertDontSee('06/11/2026');
        $this->assertSame(2, $product->fresh()->stock_quantity);
    }

    public function test_workshop_schedule_is_only_a_note_until_stock_is_explicitly_transferred(): void
    {
        $this->actingAs(User::create(['code' => 'schedule-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $product = Product::create(['code' => 'SCHED-'.bin2hex(random_bytes(4)), 'name' => 'Test', 'fabric' => '', 'size' => 'XXL', 'stock_quantity' => 0]);
        $od = \App\Models\WorkshopOrder::create(['product_code' => $product->code, 'supply_source' => 'mua_ngoai',
            'returned_date' => '2026-11-06', 'quantity' => 2, 'status' => 'da_od',
            'size_quantities' => [['product_id' => $product->id, 'quantity' => 2]]]);
        $this->get(route('products.show', $product))->assertOk()->assertSee('Dự kiến hàng về')
            ->assertSee('06/11/2026')->assertSee('XXL: 2')->assertSee('data-incoming-workshop="'.$od->id.'"', false);
        $this->assertSame(0, $product->fresh()->stock_quantity);
        $this->assertNull($od->fresh()->stocked_at);
        $this->postJson(route('workshop-orders.stock', $od))->assertOk();
        $this->assertSame(2, $product->fresh()->stock_quantity);
        $this->get(route('products.show', $product))->assertOk()->assertDontSee('data-incoming-workshop="'.$od->id.'"', false);
    }

    public function test_grouped_stock_can_be_edited_inline_without_changing_rental_links(): void
    {
        $this->actingAs(User::create(['code' => 'inline-stock-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $product = Product::create(['code' => 'INLINE-'.bin2hex(random_bytes(4)), 'name' => 'Test', 'fabric' => '', 'size' => 'S', 'stock_quantity' => 2]);
        $order = Order::create(['product_id' => $product->id, 'closer_name' => 'Test', 'order_name' => 'Đang thuê',
            'pickup_date' => '2031-01-01', 'event_date' => '2031-01-02', 'return_date' => '2031-01-03',
            'status' => 'da_gui', 'quantity' => 2, 'stock_decreased_at' => now()]);
        $rental = $order->items()->create(['product_id' => $product->id, 'quantity' => 2, 'stock_held' => 2]);
        $data = ['code' => $product->code, 'name' => 'Tên đã sửa', 'rental_price_text' => 'Liên hệ',
            'variants' => [['id' => $product->id, 'size' => 'S', 'stock_quantity' => 3, 'original_quantity' => 2],
                ['size' => 'M', 'stock_quantity' => 2, 'original_quantity' => 0]]];
        $response = $this->patchJson(route('products.inline-stock', $product), $data)->assertOk();
        $this->assertSame(3, $product->fresh()->stock_quantity);
        $this->assertSame('Tên đã sửa', $product->fresh()->name);
        $this->assertSame($product->id, $rental->fresh()->product_id);
        $this->assertSame(2, (int) $rental->fresh()->stock_held);
        $this->assertSame(2, Product::where('code', $product->code)->count());
        $this->get(route('products.show', $product))->assertOk()->assertSee('data-stock-editor', false)
            ->assertDontSee('href="'.route('products.edit', $product).'"', false);
        $this->patchJson(route('products.inline-stock', $product), $data)->assertUnprocessable();
        $this->assertSame(3, $product->fresh()->stock_quantity);
    }

    public function test_product_shows_stock_in_warehouse_and_out_on_rental_separately(): void
    {
        $this->actingAs(User::create(['code' => 'stock-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $product = Product::create(['code' => 'SPLIT-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'fabric' => 'Lụa', 'size' => 'M', 'stock_quantity' => 5]);
        $order = Order::create(['product_id' => $product->id, 'closer_name' => 'Test', 'order_name' => 'Test',
            'pickup_date' => '2031-01-01', 'event_date' => '2031-01-02', 'return_date' => '2031-01-03',
            'status' => 'da_gui', 'quantity' => 5, 'stock_decreased_at' => now()]);
        $order->items()->create(['product_id' => $product->id, 'quantity' => 5, 'stock_held' => 5]);
        $this->get('/products/'.$product->id)->assertOk()
            ->assertViewHas('totalQuantity', fn ($value) => (int) $value === 5)
            ->assertViewHas('totalRented', 5)
            ->assertSee('Tổng số hàng')->assertSee('Đang cho thuê')->assertSee('Còn trong kho');
    }
}
