<?php

namespace App\Services;

use App\Models\ProductExpectedReceipt;
use App\Models\User;
use App\Models\WorkshopOrder;
use App\Models\WorkshopOrderEdit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkshopOrderUndoService
{
    public function record(int $userId, ?WorkshopOrder $order, callable $change): WorkshopOrder
    {
        return DB::transaction(function () use ($userId, $order, $change) {
            User::whereKey($userId)->lockForUpdate()->firstOrFail();
            $locked = $order ? WorkshopOrder::whereKey($order->id)->lockForUpdate()->firstOrFail() : null;
            $before = $locked?->getAttributes();
            $receipts = $locked ? ProductExpectedReceipt::where('workshop_order_id', $locked->id)
                ->lockForUpdate()->get()->map->getAttributes()->all() : [];
            $result = $change($locked);
            $after = $result->exists ? $result->fresh()->getAttributes() : null;
            // A no-op auto-save must not replace the user's last actual edit.
            $content = fn ($data) => $data ? array_diff_key($data, array_flip(['created_at', 'updated_at'])) : null;
            if ($content($before) != $content($after)) {
                WorkshopOrderEdit::create(['user_id' => $userId, 'workshop_order_id' => $result->id,
                    'before' => $before, 'after' => $after, 'receipts' => $receipts]);
            }
            return $result;
        });
    }

    public function undo(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            User::whereKey($userId)->lockForUpdate()->firstOrFail();
            $edit = WorkshopOrderEdit::where('user_id', $userId)
                ->latest('id')->limit(5)->lockForUpdate()->get()->firstWhere('undone_at', null);
            if (!$edit) $this->reject('Bạn chưa có thao tác nào để hoàn tác.');
            $order = WorkshopOrder::whereKey($edit->workshop_order_id)->lockForUpdate()->first();
            // Never overwrite a subsequent edit, even when its values happen to match.
            if (WorkshopOrderEdit::where('workshop_order_id', $edit->workshop_order_id)
                ->where('id', '>', $edit->id)
                ->where(fn ($query) => $query->whereNull('undone_at')->orWhere('user_id', '!=', $userId))->exists()
                || ($order?->getAttributes() != $edit->after)) {
                $this->reject('Không thể hoàn tác: dòng này đã có thay đổi sau đó. Dữ liệu của người khác được giữ nguyên.');
            }
            if (($edit->before['stocked_at'] ?? null) || ($edit->after['stocked_at'] ?? null)
                || ($edit->before['purchase_plan'] ?? null) || ($edit->after['purchase_plan'] ?? null)) {
                $this->reject('Dòng đã nhập kho hoặc đồng bộ từ đơn khách cần được điều chỉnh tại chức năng tương ứng.');
            }
            $currentReceipts = ProductExpectedReceipt::where('workshop_order_id', $edit->workshop_order_id)->lockForUpdate()->get();
            if ($currentReceipts->isNotEmpty()) $this->reject('Dòng đã có phiếu nhập kho, không thể hoàn tác tại đây.');
            if (collect($edit->receipts)->contains(fn ($receipt) => !empty($receipt['received_at']))) {
                $this->reject('Không thể hoàn tác dòng đã nhận hàng vào kho.');
            }
            if ($edit->before === null) {
                $order->delete();
            } else {
                $restored = $order ?? new WorkshopOrder;
                $restored->setRawAttributes($edit->before);
                $restored->timestamps = false;
                $restored->save();
                foreach ($edit->receipts as $receipt) {
                    DB::table('product_expected_receipts')->insert($receipt);
                }
            }
            $edit->update(['undone_at' => now()]);
        });
    }

    private function reject(string $message): void
    {
        throw ValidationException::withMessages(['undo' => $message]);
    }
}
