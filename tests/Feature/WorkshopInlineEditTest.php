<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\WorkshopOrder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkshopInlineEditTest extends TestCase
{
    use DatabaseTransactions;

    public function test_order_without_code_keeps_its_uploaded_image_when_assigned_a_code(): void
    {
        Storage::fake('public');
        $this->actingAs(User::create(['code' => 'photo-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $data = ['product_code' => WorkshopOrder::NO_PRODUCT_CODE,
            'supply_source' => WorkshopOrder::SUPPLY_OUTSIDE, 'quantity' => 2, 'unit_price' => 50000,
            'status' => WorkshopOrder::DEFAULT_STATUS, 'size_note' => "M: 1\nL: 1"];
        $image = UploadedFile::fake()->createWithContent('pasted.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='
        ));
        $response = $this->post('/workshop-orders', $data + ['image' => $image], ['Accept' => 'application/json']);
        $response->assertCreated()->assertJsonPath('product_name', 'Chưa tạo mã')->assertJsonPath('total', 100000);
        $item = WorkshopOrder::latest('id')->firstOrFail();
        Storage::disk('public')->assertExists($item->image_path);
        $imagePath = $item->image_path;
        $this->get('/workshop-orders?supply_source=mua_ngoai')->assertOk()
            ->assertSee($item->image_url)->assertSee('name="size_note" rows="1" wrap="soft"', false);
        $this->putJson('/workshop-orders/'.$item->id, $data)->assertOk()->assertJsonPath('image', $item->image_url);
        $product = Product::create(['code' => 'PIC-'.bin2hex(random_bytes(4)), 'name' => 'Ảnh đã có mã',
            'size' => 'M', 'fabric' => '', 'stock_quantity' => 0]);
        $data['product_code'] = $product->code;
        $this->putJson('/workshop-orders/'.$item->id, $data)->assertOk()->assertJsonPath('product_name', $product->name);
        $this->assertSame($imagePath, $item->fresh()->image_path);
        $this->assertSame("M: 1\nL: 1", $item->fresh()->size_note);
        $this->post('/workshop-orders', $data + ['image' => UploadedFile::fake()->create('bad.txt', 1, 'text/plain')],
            ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('image');
    }

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
        $data['status'] = WorkshopOrder::STATUS_ORDERED;
        $this->putJson('/workshop-orders/'.$item->id, $data)->assertOk();
        $this->assertSame(WorkshopOrder::STATUS_ORDERED, $item->fresh()->status);
        $this->get('/workshop-orders?status=da_od')->assertOk()
            ->assertSee('Đã od')->assertSee('class="is-ordered"', false)->assertSee($product->code);
        $data['status'] = WorkshopOrder::STATUS_ARRIVED;
        $this->putJson('/workshop-orders/'.$item->id, $data)->assertOk();
        $this->assertTrue($item->fresh()->hasArrived());
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
