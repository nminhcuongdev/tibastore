<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\WorkshopOrder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkshopInlineEditTest extends TestCase
{
    use DatabaseTransactions;

    public function test_inline_rows_save_and_validate_without_redirecting(): void
    {
        $this->actingAs(User::create(['code' => 'inline-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $product = Product::create(['code' => 'WI-'.bin2hex(random_bytes(4)), 'name' => 'Áo thử',
            'size' => 'M', 'fabric' => 'Lụa', 'stock_quantity' => 1, 'image_path' => 'products/test.jpg']);
        $data = ['product_code' => $product->code, 'supply_source' => WorkshopOrder::DEFAULT_SUPPLY_SOURCE,
            'quantity' => 2, 'unit_price' => 100000, 'status' => WorkshopOrder::DEFAULT_STATUS,
            'size_note' => 'M: 2', 'order_name' => 'Đơn thử', 'ordered_date' => '2026-11-01', 'returned_date' => '2026-11-02'];
        $item = WorkshopOrder::create($data);
        $this->get('/workshop-orders')->assertOk()->assertSee('data-inline-edit', false)
            ->assertSee('workshop-new-row', false)->assertSee('data-new-row', false);
        $this->postJson('/workshop-orders', $data)->assertCreated()
            ->assertJsonPath('total', 200000)->assertJsonPath('product_name', 'Áo thử')
            ->assertJsonStructure(['update_url', 'quantity', 'image']);
        $data['quantity'] = 3;
        $this->putJson('/workshop-orders/'.$item->id, $data)->assertOk()->assertJsonPath('total', 300000)
            ->assertJsonPath('product_name', 'Áo thử');
        $this->assertSame(3, $item->fresh()->quantity);
        $data['returned_date'] = '2026-10-31';
        $this->putJson('/workshop-orders/'.$item->id, $data)->assertUnprocessable()->assertJsonValidationErrors('returned_date');
        $data['returned_date'] = null;
        $data['product_code'] = 'MISSING-CODE';
        $this->putJson('/workshop-orders/'.$item->id, $data)->assertUnprocessable()->assertJsonValidationErrors('product_code');
        $item->update(['product_code' => 'LEGACY-MISSING']);
        $data['product_code'] = 'LEGACY-MISSING';
        $this->putJson('/workshop-orders/'.$item->id, $data)->assertOk();
    }
}
