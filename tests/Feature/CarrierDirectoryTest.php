<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CarrierDirectoryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_directory_groups_duplicate_addresses_and_filters_province_and_carrier(): void
    {
        $this->actingAs(User::create(['code' => 'dir-'.bin2hex(random_bytes(5)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $product = Product::create(['code' => 'DIR-'.bin2hex(random_bytes(5)), 'name' => 'Test',
            'fabric' => 'Lụa', 'size' => 'M', 'stock_quantity' => 10]);
        $carrier = 'Xe thử '.bin2hex(random_bytes(5));
        $base = ['product_id' => $product->id, 'closer_name' => 'Test', 'order_name' => 'Test',
            'pickup_date' => '2031-01-01', 'event_date' => '2031-01-02', 'return_date' => '2031-01-03',
            'carrier' => $carrier, 'region' => array_key_first(Order::regions()), 'quantity' => 1];
        Order::create($base + ['address' => 'Sở Dầu, Hồng Bàng, Hải Phòng']);
        Order::create($base + ['address' => 'Sở Dầu, Hồng Bàng, Hải Phòng']);
        Order::create($base + ['address' => 'Cầu Giấy, Hà Nội']);
        Order::create($base + ['address' => 'Chưa ghi tỉnh']);
        $this->get('/carrier-directory?'.http_build_query(['carrier' => $carrier]))->assertOk()
            ->assertSee('Hải Phòng')->assertSee('Hà Nội')->assertSee('Chưa xác định')
            ->assertViewHas('entries', fn ($entries) => $entries->total() === 3)
            ->assertViewHas('orderCount', 4);
        $this->get('/carrier-directory?'.http_build_query(['carrier' => $carrier, 'province' => 'Hải Phòng']))
            ->assertOk()->assertViewHas('entries', fn ($entries) => $entries->total() === 1)
            ->assertViewHas('orderCount', 2);
        $this->get('/carrier-directory?'.http_build_query(['carrier' => $carrier, 'q' => 'cau giay']))
            ->assertOk()->assertViewHas('entries', fn ($entries) => $entries->total() === 1);
    }

    public function test_directory_requires_login(): void
    {
        $this->get('/carrier-directory')->assertRedirect('/login');
    }
}
