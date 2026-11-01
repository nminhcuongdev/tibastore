<?php

namespace Tests\Feature;

use App\Models\DraftInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DraftInvoiceTest extends TestCase
{
    use DatabaseTransactions;

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
        $this->post('/draft-invoices', $data)->assertSessionHasNoErrors()->assertRedirect();
        $invoice = DraftInvoice::where('customer_name', $data['customer_name'])->firstOrFail();
        $this->assertEquals(2965000, $invoice->total);
        $this->get('/draft-invoices/'.$invoice->id.'/edit')->assertOk()->assertSee('Chau test invoice')->assertSee('invoice-items', false);
        $this->get('/draft-invoices?q=0901122456')->assertOk()->assertSee('Chau test invoice');
        $data['items'] = [['name' => 'Quạt', 'quantity' => 1.5, 'unit_price' => 35000]];
        $this->put('/draft-invoices/'.$invoice->id, $data)->assertSessionHasNoErrors();
        $this->assertEquals(52500, $invoice->fresh()->total);
        $data['items'][0]['unit_price'] = -1;
        $this->postJson('/draft-invoices', $data)->assertUnprocessable()->assertJsonValidationErrors('items.0.unit_price');
        $data['items'] = [];
        $this->postJson('/draft-invoices', $data)->assertUnprocessable()->assertJsonValidationErrors('items');
        $this->delete('/draft-invoices/'.$invoice->id)->assertRedirect('/draft-invoices');
        $this->assertNull($invoice->fresh());
    }

    public function test_guest_cannot_access_draft_invoices(): void
    {
        $this->get('/draft-invoices')->assertRedirect('/login');
        $this->post('/draft-invoices', [])->assertRedirect('/login');
    }
}
