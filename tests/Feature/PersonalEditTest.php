<?php

namespace Tests\Feature;

use App\Models\DraftInvoice;
use App\Models\Order;
use App\Models\PersonalEdit;
use App\Models\Product;
use App\Models\ProductExpectedReceipt;
use App\Models\User;
use App\Models\WorkshopStockTransfer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PersonalEditTest extends TestCase
{
    use DatabaseTransactions;

    private function account(): User
    {
        return User::create(['code' => 'personal-'.bin2hex(random_bytes(4)), 'name' => 'History test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]);
    }

    private function invoiceData(string $name): array
    {
        return ['customer_name' => $name, 'invoice_date' => '2026-11-03',
            'items' => [['name' => 'Test', 'quantity' => 1, 'unit_price' => 100]]];
    }

    public function test_invoice_history_is_limited_to_five_and_redoes_in_order(): void
    {
        $this->actingAs($this->account());
        $invoice = DraftInvoice::create($this->invoiceData('0'));
        foreach (range(1, 6) as $number) {
            $this->put('/draft-invoices/'.$invoice->id, $this->invoiceData((string) $number))->assertSessionHasNoErrors();
        }
        foreach ([5, 4, 3, 2, 1] as $number) {
            $this->postJson('/personal-edits/draft-invoices/undo')->assertOk();
            $this->assertSame((string) $number, $invoice->fresh()->customer_name);
        }
        $this->postJson('/personal-edits/draft-invoices/undo')->assertUnprocessable();
        foreach (range(2, 6) as $number) {
            $this->postJson('/personal-edits/draft-invoices/redo')->assertOk();
            $this->assertSame((string) $number, $invoice->fresh()->customer_name);
        }
        $this->postJson('/personal-edits/draft-invoices/redo')->assertUnprocessable();
        $this->postJson('/personal-edits/draft-invoices/undo')->assertOk();
        $this->put('/draft-invoices/'.$invoice->id, $this->invoiceData('new'))->assertSessionHasNoErrors();
        $this->postJson('/personal-edits/draft-invoices/redo')->assertUnprocessable();
    }

    public function test_invoice_create_delete_and_redo_restore_whole_document(): void
    {
        $this->actingAs($this->account());
        $this->post('/draft-invoices', $this->invoiceData('Customer'))->assertSessionHasNoErrors();
        $invoice = DraftInvoice::latest('id')->firstOrFail();
        $this->postJson('/personal-edits/draft-invoices/undo')->assertOk();
        $this->assertNull($invoice->fresh());
        $this->postJson('/personal-edits/draft-invoices/redo')->assertOk();
        $this->assertSame('Customer', $invoice->fresh()->customer_name);
        $this->delete('/draft-invoices/'.$invoice->id)->assertRedirect();
        $this->postJson('/personal-edits/draft-invoices/undo')->assertOk();
        $this->assertSame(100.0, $invoice->fresh()->total);
        $this->postJson('/personal-edits/draft-invoices/redo')->assertOk();
        $this->assertNull($invoice->fresh());
    }

    public function test_other_accounts_and_later_changes_are_protected(): void
    {
        $owner = $this->account(); $other = $this->account();
        $invoice = DraftInvoice::create($this->invoiceData('Original'));
        $this->actingAs($owner)->put('/draft-invoices/'.$invoice->id, $this->invoiceData('Owner'))->assertSessionHasNoErrors();
        $this->actingAs($other)->postJson('/personal-edits/draft-invoices/undo', ['user_id' => $owner->id])->assertUnprocessable();
        $this->put('/draft-invoices/'.$invoice->id, $this->invoiceData('Other'))->assertSessionHasNoErrors();
        $this->actingAs($owner)->postJson('/personal-edits/draft-invoices/undo')->assertUnprocessable();
        $this->assertSame('Other', $invoice->fresh()->customer_name);
        $this->postJson('/personal-edits/products/undo')->assertUnprocessable();
    }

    public function test_product_creation_undo_redo_includes_all_sizes_and_stock_history(): void
    {
        $this->actingAs($this->account());
        $code = 'HIST-'.bin2hex(random_bytes(4));
        $this->post('/products', ['code' => $code, 'name' => 'Product', 'variants' => [
            ['size' => 'M', 'stock_quantity' => 3], ['size' => 'L', 'stock_quantity' => 5]]])->assertSessionHasNoErrors();
        $this->assertSame(2, Product::where('code', $code)->count());
        $this->assertSame(2, WorkshopStockTransfer::where('product_code', $code)->count());
        $this->postJson('/personal-edits/products/undo')->assertOk();
        $this->assertSame(0, Product::where('code', $code)->count());
        $this->assertSame(0, WorkshopStockTransfer::where('product_code', $code)->count());
        $this->postJson('/personal-edits/products/redo')->assertOk();
        $this->assertSame(8, (int) Product::where('code', $code)->sum('stock_quantity'));
        $this->assertSame(2, WorkshopStockTransfer::where('product_code', $code)->count());
    }

    public function test_order_status_undo_redo_reconciles_stock_and_items(): void
    {
        $this->actingAs($this->account());
        $product = Product::create(['code' => 'ORDER-'.bin2hex(random_bytes(4)), 'name' => 'Product', 'size' => 'M', 'fabric' => '', 'stock_quantity' => 10]);
        $this->post('/orders', ['closer_name' => 'Test', 'order_name' => 'History order', 'pickup_date' => '2032-03-03',
            'event_date' => '2032-03-04', 'return_date' => '2032-03-05', 'source' => Order::DEFAULT_SOURCE,
            'status' => Order::DEFAULT_STATUS, 'items' => [['product_id' => $product->id, 'quantity' => 2, 'rental_price' => 10000]]])->assertSessionHasNoErrors();
        $order = Order::latest('id')->firstOrFail();
        $this->patchJson('/orders/'.$order->id.'/status', ['status' => 'da_gui'])->assertOk();
        $this->assertSame(8, $product->fresh()->stock_quantity);
        $this->postJson('/personal-edits/orders/undo')->assertOk();
        $this->assertSame(10, $product->fresh()->stock_quantity);
        $this->assertSame(Order::DEFAULT_STATUS, $order->fresh()->status);
        $this->postJson('/personal-edits/orders/redo')->assertOk();
        $this->assertSame(8, $product->fresh()->stock_quantity);
        $this->assertSame('da_gui', $order->fresh()->status);
    }

    public function test_undo_does_not_cascade_delete_new_related_receipts(): void
    {
        $this->actingAs($this->account());
        $code = 'DEPEND-'.bin2hex(random_bytes(4));
        $this->post('/products', ['code' => $code, 'name' => 'Product', 'variants' => [['size' => 'M', 'stock_quantity' => 0]]])->assertSessionHasNoErrors();
        $product = Product::where('code', $code)->firstOrFail();
        $receipt = ProductExpectedReceipt::create(['product_id' => $product->id, 'expected_receive_date' => '2032-01-01', 'expected_receive_quantity' => 1]);
        $this->postJson('/personal-edits/products/undo')->assertUnprocessable();
        $this->assertNotNull($receipt->fresh());
        $this->assertNotNull($product->fresh());
    }

    public function test_buttons_render_and_invalid_requests_do_not_create_history(): void
    {
        $this->actingAs($this->account());
        foreach (['/products', '/orders', '/draft-invoices', '/draft-invoices/create'] as $url) {
            $this->get($url)->assertOk()->assertSee('data-personal-action="undo"', false)->assertSee('data-personal-action="redo"', false);
        }
        $this->postJson('/draft-invoices', [])->assertUnprocessable();
        $this->assertSame(0, PersonalEdit::count());
        $this->postJson('/personal-edits/users/undo')->assertNotFound();
    }
}
