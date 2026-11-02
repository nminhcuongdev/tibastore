<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkshopOrder;
use App\Models\WorkshopOrderEdit;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkshopOrderUndoTest extends TestCase
{
    use DatabaseTransactions;

    private function account(): User
    {
        return User::create(['code' => 'undo-'.bin2hex(random_bytes(4)), 'name' => 'Undo test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]);
    }

    private function data(int $quantity = 1): array
    {
        return ['product_code' => WorkshopOrder::NO_PRODUCT_CODE, 'supply_source' => WorkshopOrder::SUPPLY_OUTSIDE,
            'quantity' => $quantity, 'unit_price' => 100, 'status' => WorkshopOrder::DEFAULT_STATUS];
    }

    public function test_five_edits_can_be_undone_in_order_but_not_a_sixth(): void
    {
        $this->actingAs($this->account());
        $order = WorkshopOrder::create($this->data());
        foreach (range(2, 7) as $quantity) {
            $this->putJson('/workshop-orders/'.$order->id, $this->data($quantity))->assertOk();
        }
        foreach ([6, 5, 4, 3, 2] as $quantity) {
            $this->postJson('/workshop-orders/undo-last-edit')->assertOk();
            $this->assertSame($quantity, $order->fresh()->quantity);
        }
        $this->postJson('/workshop-orders/undo-last-edit')->assertUnprocessable();
        $this->assertSame(2, $order->fresh()->quantity);
    }

    public function test_an_account_cannot_undo_someone_elses_edit_even_with_a_forged_user_id(): void
    {
        $owner = $this->account();
        $other = $this->account();
        $order = WorkshopOrder::create($this->data());
        $this->actingAs($owner)->putJson('/workshop-orders/'.$order->id, $this->data(2))->assertOk();
        $this->actingAs($other)->postJson('/workshop-orders/undo-last-edit', ['user_id' => $owner->id])->assertUnprocessable();
        $this->assertSame(2, $order->fresh()->quantity);
        $this->putJson('/workshop-orders/'.$order->id, $this->data(3))->assertOk();
        $this->actingAs($owner)->postJson('/workshop-orders/undo-last-edit')->assertUnprocessable();
        $this->assertSame(3, $order->fresh()->quantity);
        $this->assertSame(0, WorkshopOrderEdit::whereNotNull('undone_at')->count());
    }

    public function test_other_accounts_edits_on_other_rows_are_preserved(): void
    {
        $owner = $this->account();
        $other = $this->account();
        $first = WorkshopOrder::create($this->data());
        $second = WorkshopOrder::create($this->data());
        $this->actingAs($owner)->putJson('/workshop-orders/'.$first->id, $this->data(2))->assertOk();
        $this->actingAs($other)->putJson('/workshop-orders/'.$second->id, $this->data(3))->assertOk();
        $this->actingAs($owner)->postJson('/workshop-orders/undo-last-edit')->assertOk();
        $this->assertSame(1, $first->fresh()->quantity);
        $this->assertSame(3, $second->fresh()->quantity);
    }

    public function test_delete_and_create_can_be_undone_without_repeated_undo(): void
    {
        $this->actingAs($this->account());
        $this->postJson('/workshop-orders', $this->data())->assertCreated();
        $order = WorkshopOrder::latest('id')->firstOrFail();
        $this->delete('/workshop-orders/'.$order->id)->assertRedirect();
        $this->assertNull($order->fresh());
        $this->postJson('/workshop-orders/undo-last-edit')->assertOk();
        $this->assertNotNull($order->fresh());
        $this->postJson('/workshop-orders/undo-last-edit')->assertOk();
        $this->assertNull($order->fresh());
        $this->postJson('/workshop-orders/undo-last-edit')->assertUnprocessable();
    }

    public function test_stock_changes_and_untracked_changes_cannot_be_overwritten(): void
    {
        $this->actingAs($this->account());
        $order = WorkshopOrder::create($this->data());
        $this->putJson('/workshop-orders/'.$order->id, $this->data(2))->assertOk();
        $order->refresh()->update(['stocked_at' => now()]);
        $this->postJson('/workshop-orders/undo-last-edit')->assertUnprocessable();
        $this->assertSame(2, $order->fresh()->quantity);
    }

    public function test_five_undone_edits_can_be_redone_in_order(): void
    {
        $this->actingAs($this->account());
        $order = WorkshopOrder::create($this->data());
        foreach (range(2, 6) as $quantity) {
            $this->putJson('/workshop-orders/'.$order->id, $this->data($quantity))->assertOk();
        }
        foreach (range(1, 5) as $unused) $this->postJson('/workshop-orders/undo-last-edit')->assertOk();
        foreach (range(2, 6) as $quantity) {
            $this->postJson('/workshop-orders/redo-last-edit')->assertOk();
            $this->assertSame($quantity, $order->fresh()->quantity);
        }
        $this->postJson('/workshop-orders/redo-last-edit')->assertUnprocessable();
        $this->postJson('/workshop-orders/undo-last-edit')->assertOk();
        $this->assertSame(5, $order->fresh()->quantity);
    }

    public function test_new_edits_clear_redo_and_other_users_cannot_redo_your_changes(): void
    {
        $owner = $this->account();
        $other = $this->account();
        $order = WorkshopOrder::create($this->data());
        $this->actingAs($owner)->putJson('/workshop-orders/'.$order->id, $this->data(2))->assertOk();
        $this->postJson('/workshop-orders/undo-last-edit')->assertOk();
        $this->actingAs($other)->postJson('/workshop-orders/redo-last-edit', ['user_id' => $owner->id])->assertUnprocessable();
        $this->putJson('/workshop-orders/'.$order->id, $this->data(3))->assertOk();
        $this->actingAs($owner)->postJson('/workshop-orders/redo-last-edit')->assertUnprocessable();
        $this->assertSame(3, $order->fresh()->quantity);
        $this->putJson('/workshop-orders/'.$order->id, $this->data(4))->assertOk();
        $this->postJson('/workshop-orders/redo-last-edit')->assertUnprocessable();
    }

    public function test_creation_and_deletion_can_be_redone(): void
    {
        $this->actingAs($this->account());
        $this->postJson('/workshop-orders', $this->data())->assertCreated();
        $order = WorkshopOrder::latest('id')->firstOrFail();
        $this->postJson('/workshop-orders/undo-last-edit')->assertOk();
        $this->postJson('/workshop-orders/redo-last-edit')->assertOk();
        $this->assertNotNull($order->fresh());
        $this->delete('/workshop-orders/'.$order->id)->assertRedirect();
        $this->postJson('/workshop-orders/undo-last-edit')->assertOk();
        $this->postJson('/workshop-orders/redo-last-edit')->assertOk();
        $this->assertNull($order->fresh());
    }
}
