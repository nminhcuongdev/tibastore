<?php

namespace Tests\Feature;

use App\Models\DraftInvoice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DraftInvoiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_fractional_days_are_saved_and_validated(): void
    {
        $this->actingAs(User::create(['code' => 'days-'.bin2hex(random_bytes(5)),
            'name' => 'Test', 'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $data = ['invoice_date' => '2026-11-04', 'customer_name' => 'Fractional days',
            'items' => [['name' => 'Set rồng', 'quantity' => 6, 'unit_price' => 90000, 'days' => '1,5']]];
        $this->post('/draft-invoices', $data)->assertSessionHasNoErrors()->assertRedirect();
        $invoice = DraftInvoice::latest('id')->firstOrFail();
        $this->assertEquals(810000, $invoice->total);
        $this->assertEquals(1.5, $invoice->items[0]['days']);
        $data['items'][0]['days'] = '1.6';
        $this->put('/draft-invoices/'.$invoice->id, $data)->assertSessionHasNoErrors();
        $this->assertEquals(864000, $invoice->fresh()->total);
        foreach (['0', '-1', 'abc', '1,234', '10001'] as $invalid) {
            $data['items'][0]['days'] = $invalid;
            $this->postJson('/draft-invoices', $data)->assertUnprocessable()->assertJsonValidationErrors('items.0.days');
        }
        $this->get('/draft-invoices/'.$invoice->id.'/edit')->assertOk()->assertSee('Số ngày');
    }

    public function test_drafts_can_be_saved_edited_searched_and_deleted(): void
    {
        $this->actingAs(User::create(['code' => 'inv-'.bin2hex(random_bytes(5)),
            'name' => 'Test', 'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $data = ['invoice_date' => '2026-10-31', 'customer_name' => 'Chau test invoice',
            'phone' => '0901122456', 'address' => 'Trường Đại học Luật Hà Nội', 'tax_code' => '',
            'items' => [
                ['name' => 'Yếm hồng tay bèo', 'unit' => 'Bộ', 'quantity' => 12, 'unit_price' => 105000],
                ['name' => 'Nam trắng + vạt', 'unit' => 'Bộ', 'quantity' => 4, 'unit_price' => 125000],
                ['name' => 'Nón quai thao', 'unit' => 'Cái', 'quantity' => 12, 'unit_price' => 60000],
                ['name' => 'Hoa cài tóc', 'unit' => 'Cái', 'quantity' => 12, 'unit_price' => 20000],
                ['name' => 'Quạt', 'unit' => 'Cái', 'quantity' => 7, 'unit_price' => 35000],
            ]];
        $this->get('/draft-invoices/create')->assertOk()->assertSee('invoice-items', false);
        $code = 'INV-'.bin2hex(random_bytes(5));
        Product::create(['code' => $code, 'name' => 'Áo mẫu', 'size' => 'M', 'fabric' => 'Lụa', 'category' => 'Áo biểu diễn', 'stock_quantity' => 5]);
        Product::create(['code' => $code, 'name' => 'Áo mẫu', 'size' => 'L', 'fabric' => 'Lụa', 'stock_quantity' => 5,
            'image_path' => 'products/invoice-test.jpg']);
        $this->get('/draft-invoices/create')->assertOk()->assertViewHas('productCodes', function ($codes) use ($code) {
            $matches = array_values(array_filter($codes, fn ($product) => $product['code'] === $code));
            return count($matches) === 1 && $matches[0]['category'] === 'Áo biểu diễn'
                && str_ends_with($matches[0]['image'], '/storage/products/invoice-test.jpg');
        })->assertSee('invoice-product-picker', false)->assertSee('/brand/tiba-logo?v=');
        $this->get('/draft-invoices/logo')->assertOk()->assertHeader('Content-Type', 'image/png');
        $data['items'][0]['code'] = $code;
        $this->post('/draft-invoices', $data)->assertSessionHasNoErrors()->assertRedirect();
        $invoice = DraftInvoice::where('customer_name', $data['customer_name'])->firstOrFail();
        $this->assertEquals(2965000, $invoice->total);
        $this->assertSame($code, $invoice->items[0]['code']);
        $this->get('/draft-invoices/'.$invoice->id.'/edit')->assertOk()->assertSee('Chau test invoice')->assertSee('invoice-items', false);
        $this->get('/draft-invoices?q=0901122456')->assertOk()->assertSee('Chau test invoice');
        $data['items'] = [['name' => 'Quạt', 'quantity' => 1.5, 'unit_price' => 35000]];
        $this->put('/draft-invoices/'.$invoice->id, $data)->assertSessionHasNoErrors();
        $this->assertEquals(52500, $invoice->fresh()->total);
        $data['discount_type'] = 'percent';
        $data['discount_value'] = 12.5;
        $this->put('/draft-invoices/'.$invoice->id, $data)->assertSessionHasNoErrors();
        $this->assertEquals(6563, $invoice->fresh()->discount_amount);
        $this->assertEquals(45937, $invoice->fresh()->total);
        $data['discount_type'] = 'fixed';
        $data['discount_value'] = 5000;
        $this->put('/draft-invoices/'.$invoice->id, $data)->assertSessionHasNoErrors();
        $this->assertEquals(47500, $invoice->fresh()->total);
        $this->get('/draft-invoices?q=0901122456')->assertOk()->assertSee('47.500');
        $data['discount_value'] = 52501;
        $this->postJson('/draft-invoices', $data)->assertUnprocessable()->assertJsonValidationErrors('discount_value');
        $data['discount_type'] = 'percent';
        $data['discount_value'] = 101;
        $this->postJson('/draft-invoices', $data)->assertUnprocessable()->assertJsonValidationErrors('discount_value');
        $data['discount_value'] = -1;
        $this->postJson('/draft-invoices', $data)->assertUnprocessable()->assertJsonValidationErrors('discount_value');
        $data['discount_value'] = 100;
        $this->put('/draft-invoices/'.$invoice->id, $data)->assertSessionHasNoErrors();
        $this->assertEquals(0, $invoice->fresh()->total);
        $this->get('/draft-invoices/'.$invoice->id.'/edit')->assertOk();
        $data['items'][0]['unit_price'] = -1;
        $this->postJson('/draft-invoices', $data)->assertUnprocessable()->assertJsonValidationErrors('items.0.unit_price');
        $data['items'] = [];
        $this->postJson('/draft-invoices', $data)->assertUnprocessable()->assertJsonValidationErrors('items');
        $this->delete('/draft-invoices/'.$invoice->id)->assertRedirect('/draft-invoices');
        $this->assertNull($invoice->fresh());
    }

    public function test_guest_cannot_access_draft_invoices(): void
    {
        $this->get('/brand/tiba-logo')->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get('/draft-invoices')->assertRedirect('/login');
        $this->post('/draft-invoices', [])->assertRedirect('/login');
    }
}
