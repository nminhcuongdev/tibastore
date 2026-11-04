<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CustomerSuggestionsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_contacts_can_be_found_by_name_phone_and_address_without_duplicates(): void
    {
        $this->actingAs(User::create(['code' => 'contacts-'.bin2hex(random_bytes(5)),
            'name' => 'Test', 'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $key = 'Contact'.bin2hex(random_bytes(5));
        $product = Product::create(['code' => $key, 'name' => 'Áo', 'size' => 'M', 'fabric' => '', 'stock_quantity' => 1]);
        $data = ['closer_name' => 'Test', 'order_name' => $key, 'product_id' => $product->id,
            'pickup_date' => '2031-01-01', 'event_date' => '2031-01-02', 'return_date' => '2031-01-03',
            'phone' => '0912 345-678', 'address' => $key.' đường cũ'];
        Order::create($data);
        Order::create($data);
        $data['address'] = $key.' đường mới';
        Order::create($data);
        $url = '/orders/customer-suggestions?';
        $this->getJson($url.http_build_query(['field' => 'order_name', 'q' => $key]))
            ->assertOk()->assertJsonCount(2)->assertJsonPath('0.address', $data['address'])
            ->assertJsonPath('0.phone', '0912 345-678')->assertJsonMissingPath('0.latest_id');
        $this->getJson($url.http_build_query(['field' => 'phone', 'q' => '0912345678']))
            ->assertOk()->assertJsonFragment(['order_name' => $key, 'phone' => $data['phone'], 'address' => $data['address']]);
        $this->getJson($url.http_build_query(['field' => 'address', 'q' => $data['address']]))
            ->assertOk()->assertJsonCount(1);
        $this->getJson($url.'field=order_name&q=')->assertOk()->assertExactJson([]);
        $this->getJson($url.'field=password&q=test')->assertUnprocessable();
        $this->get('/orders/create')->assertOk()->assertSee('id="customer-suggestions"', false);
    }

    public function test_guests_cannot_search_customer_contacts(): void
    {
        $this->getJson('/orders/customer-suggestions?field=phone&q=0912')->assertUnauthorized();
    }
}
