<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\WorkshopOrder;
use App\Services\WorkshopDuplicateService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkshopDuplicateTest extends TestCase
{
    use DatabaseTransactions;

    private function setupAccount(): Product
    {
        $this->actingAs(User::create(['code' => 'dup-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        return Product::create(['code' => 'DUP-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'size' => 'M', 'fabric' => '', 'stock_quantity' => 0]);
    }

    public function test_auto_save_warns_across_sources_and_clears_both_rows_when_corrected(): void
    {
        $product = $this->setupAccount();
        $linked = WorkshopOrder::create(['product_code' => $product->code, 'order_name' => 'Khách Trùng',
            'supply_source' => 'xuong_may', 'quantity' => 1, 'status' => WorkshopOrder::STATUS_ORDERED]);
        $data = ['product_code' => $product->code, 'order_name' => '  KHÁCH   TRÙNG  ',
            'supply_source' => 'mua_ngoai', 'quantity' => 1, 'status' => WorkshopOrder::DEFAULT_STATUS];
        $response = $this->postJson('/workshop-orders', $data)->assertCreated();
        $manualId = $response->json('id');
        $response->assertJsonPath('duplicate_rows.'.$manualId, [$linked->id])
            ->assertJsonPath('duplicate_rows.'.$linked->id, [$manualId]);
        $this->get('/workshop-orders?focus='.$linked->id)->assertOk()
            ->assertSee('class="is-duplicate ', false)->assertSee('Nghi trùng Tên đơn và Mã hàng');
        $data['order_name'] = 'Khách khác';
        $response = $this->putJson('/workshop-orders/'.$manualId, $data)->assertOk();
        $this->assertEmpty($response->json('duplicate_rows'));
        $this->get('/workshop-orders?focus='.$linked->id)->assertOk()->assertDontSee('class="is-duplicate ', false);
        $this->assertSame(2, WorkshopOrder::where('product_code', $product->code)->count());
    }

    public function test_saving_customer_plan_warns_if_a_manual_order_already_exists(): void
    {
        $product = $this->setupAccount();
        $manual = WorkshopOrder::create(['product_code' => $product->code, 'order_name' => 'Đơn đã od tay',
            'supply_source' => 'mua_ngoai', 'quantity' => 1, 'status' => WorkshopOrder::STATUS_ORDERED]);
        $plan = ['expected_date' => '2039-01-01', 'lines' => [['product_id' => $product->id, 'quantity' => 1]]];
        $response = $this->post('/orders', ['closer_name' => 'Test', 'order_name' => $manual->order_name,
            'pickup_date' => '2039-01-02', 'event_date' => '2039-01-03', 'return_date' => '2039-01-04',
            'source' => Order::DEFAULT_SOURCE, 'status' => Order::DEFAULT_STATUS,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'rental_price' => 100,
                'fulfillment_source' => 'mua_ngoai', 'purchase_plan' => $plan]]]);
        $response->assertSessionHasNoErrors()->assertRedirect()->assertSessionHas('workshop_duplicate_warning');
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('Cảnh báo: phần od của đơn này trùng');
        $duplicates = app(WorkshopDuplicateService::class)->duplicates();
        $this->assertCount(2, $duplicates);
        $this->assertArrayHasKey($manual->id, $duplicates);
        $this->assertSame(0, $product->fresh()->stock_quantity);
    }

    public function test_different_codes_and_unnamed_or_unknown_products_are_not_marked_as_duplicates(): void
    {
        $product = $this->setupAccount();
        foreach ([[$product->code, 'One'], [$product->code.'-OTHER', 'One'],
            [$product->code, ''], [$product->code, ' '],
            [WorkshopOrder::NO_PRODUCT_CODE, 'One'], [WorkshopOrder::NO_PRODUCT_CODE, 'One']] as [$code, $name]) {
            WorkshopOrder::create(['product_code' => $code, 'order_name' => $name, 'supply_source' => 'mua_ngoai',
                'quantity' => 1, 'status' => WorkshopOrder::DEFAULT_STATUS]);
        }
        $this->assertSame([], app(WorkshopDuplicateService::class)->duplicates());
    }
}
