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

    public function test_code_suggestion_skips_existing_and_discontinued_codes(): void
    {
        $this->actingAs(User::create(['code' => 'suggest-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $prefix = 'SUG-'.bin2hex(random_bytes(4)).'-';
        foreach (['001', '002'] as $suffix) {
            Product::create(['code' => $prefix.$suffix, 'name' => 'Test', 'size' => 'M',
                'fabric' => '', 'stock_quantity' => 0, 'discontinued_at' => $suffix === '002' ? now() : null]);
        }
        $url = route('workshop-orders.suggest-product-code');
        $this->getJson($url.'?'.http_build_query(['code' => $prefix.'001']))->assertOk()
            ->assertJsonPath('exists', true)->assertJsonPath('code', $prefix.'003');
        $this->getJson($url.'?'.http_build_query(['code' => strtolower($prefix).'002']))->assertOk()
            ->assertJsonPath('exists', true)->assertJsonPath('code', strtolower($prefix).'003');
        $suggestion = $this->getJson($url)->assertOk()->json('code');
        $this->assertFalse(Product::where('code', $suggestion)->exists());
    }

    public function test_create_product_from_workshop_then_save_order_without_adding_stock(): void
    {
        $this->actingAs(User::create(['code' => 'quick-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $data = ['code' => 'NEW-'.bin2hex(random_bytes(4)), 'name' => 'Áo mới', 'size' => 'M', 'category' => 'Áo dài'];
        $this->postJson(route('workshop-orders.create-product'), $data)->assertCreated()
            ->assertJsonPath('code', $data['code'])->assertJsonPath('sizes.0.size', 'M')->assertJsonPath('category', 'Áo dài');
        $product = Product::where('code', $data['code'])->firstOrFail();
        $this->assertSame(0, $product->stock_quantity);
        $this->assertSame('Áo dài', $product->category);
        $this->get('/workshop-orders')->assertOk()->assertSee('Chọn danh mục có sẵn hoặc nhập danh mục mới');
        $this->postJson('/workshop-orders', ['product_code' => $product->code,
            'supply_source' => WorkshopOrder::SUPPLY_OUTSIDE, 'quantity' => 2,
            'status' => WorkshopOrder::DEFAULT_STATUS])->assertCreated()->assertJsonPath('product_name', $data['name']);
        $this->postJson(route('workshop-orders.create-product'), $data)->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->postJson(route('workshop-orders.create-product'), ['code' => ' ', 'name' => '', 'size' => ''])
            ->assertUnprocessable()->assertJsonValidationErrors(['code', 'name', 'size']);
        $this->assertSame(1, Product::where('code', $data['code'])->count());
    }

    public function test_new_order_defaults_to_vietnam_date_and_preserves_manual_dates(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-02 18:30:00', 'UTC'));
        try {
            $this->actingAs(User::create(['code' => 'date-'.bin2hex(random_bytes(4)), 'name' => 'Test',
                'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
            $data = ['product_code' => WorkshopOrder::NO_PRODUCT_CODE,
                'supply_source' => WorkshopOrder::SUPPLY_OUTSIDE, 'quantity' => 1,
                'status' => WorkshopOrder::DEFAULT_STATUS, 'ordered_date' => ''];
            $this->postJson('/workshop-orders', $data)->assertCreated();
            $item = WorkshopOrder::latest('id')->firstOrFail();
            $this->assertSame('2026-11-03', $item->ordered_date->toDateString());
            unset($data['ordered_date']);
            $this->putJson('/workshop-orders/'.$item->id, $data)->assertOk();
            $this->assertSame('2026-11-03', $item->fresh()->ordered_date->toDateString());
            $data['ordered_date'] = '2026-10-31';
            $this->postJson('/workshop-orders', $data)->assertCreated();
            $this->assertSame('2026-10-31', WorkshopOrder::latest('id')->firstOrFail()->ordered_date->toDateString());
        } finally {
            $this->travelBack();
        }
    }

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
            ->assertSee('workshop-new-row', false)->assertSee('data-new-row', false)
            ->assertSee('Các thay đổi được tự động lưu.')
            ->assertDontSee('class="inline-save">Lưu', false);
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
            ->assertSee('Đã od')->assertSee('class="is-duplicate is-ordered"', false)->assertSee($product->code);
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
