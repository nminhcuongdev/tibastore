<?php

namespace App\Services;

use App\Models\PersonalEdit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PersonalEditService
{
    public bool $recording = false;

    // Parents precede their dependent rows. Inventory effects belong to the same action.
    private const TABLES = ['products', 'orders', 'workshop_orders', 'order_items',
        'product_expected_receipts', 'stock_import_histories', 'workshop_stock_transfers',
        'order_reminder_preferences'];

    private function tables(string $section): array
    {
        return $section === 'draft-invoices' ? ['draft_invoices'] : self::TABLES;
    }

    private function snapshot(array $tables): array
    {
        $snapshot = [];
        foreach ($tables as $table) {
            $snapshot[$table] = DB::table($table)->orderBy('id')->lockForUpdate()->get()
                ->mapWithKeys(fn ($row) => [$row->id => (array) $row])->all();
        }
        return $snapshot;
    }

    public function record(int $userId, string $section, callable $action)
    {
        return DB::transaction(function () use ($userId, $section, $action) {
            User::whereKey($userId)->lockForUpdate()->firstOrFail();
            // Lock the snapshot until commit, so concurrent requests cannot be attributed
            // to this user. Only changed rows are retained in history and restored later.
            $before = $this->snapshot($this->tables($section));
            $this->recording = true;
            try { $response = $action(); }
            finally { $this->recording = false; }
            $after = $this->snapshot($this->tables($section));
            $changes = [];
            foreach ($before as $table => $rows) {
                foreach (array_unique(array_merge(array_keys($rows), array_keys($after[$table]))) as $id) {
                    $old = $rows[$id] ?? null;
                    $new = $after[$table][$id] ?? null;
                    if (!$this->same($old, $new)) $changes[$table][$id] = ['before' => $old, 'after' => $new];
                }
            }
            if ($changes) {
                PersonalEdit::where('user_id', $userId)->where('section', $section)
                    ->whereNotNull('undone_at')->whereNull('redo_invalidated_at')->update(['redo_invalidated_at' => now()]);
                PersonalEdit::create(['user_id' => $userId, 'section' => $section, 'changes' => $changes]);
            }
            return $response;
        });
    }

    public function replay(int $userId, string $section, bool $redo): ?int
    {
        return DB::transaction(function () use ($userId, $section, $redo) {
            User::whereKey($userId)->lockForUpdate()->firstOrFail();
            $entries = PersonalEdit::where('user_id', $userId)->where('section', $section)
                ->latest('id')->limit(5)->lockForUpdate()->get();
            $edit = $redo ? $entries->reverse()->first(fn ($entry) => $entry->undone_at && !$entry->redo_invalidated_at)
                : $entries->first(fn ($entry) => !$entry->undone_at);
            if (!$edit) $this->reject($redo ? 'Bạn chưa có thao tác nào để làm lại.' : 'Bạn chưa có thao tác nào để hoàn tác.');
            $current = $this->snapshot($this->tables($section));
            $from = $redo ? 'before' : 'after';
            $to = $redo ? 'after' : 'before';
            // Check the entire action before changing anything, including stock and lines.
            foreach ($edit->changes as $table => $rows) {
                foreach ($rows as $id => $change) {
                    if (!$this->same($current[$table][$id] ?? null, $change[$from])) $this->conflict();
                }
            }
            // A later user's change still matters even if its values were changed back.
            $later = PersonalEdit::where('id', '>', $edit->id)
                ->where(fn ($query) => $query->where('user_id', '!=', $userId)->orWhereNull('undone_at'))->get();
            foreach ($later as $other) {
                foreach ($edit->changes as $table => $rows) {
                    if (array_intersect(array_keys($rows), array_keys($other->changes[$table] ?? []))) $this->conflict();
                }
            }
            $dependencies = ['products' => ['order_items' => 'product_id', 'product_expected_receipts' => 'product_id',
                'stock_import_histories' => 'product_id'], 'orders' => ['order_items' => 'order_id',
                'order_reminder_preferences' => 'order_id', 'workshop_orders' => 'customer_order_id'],
                'workshop_orders' => ['product_expected_receipts' => 'workshop_order_id', 'workshop_stock_transfers' => 'workshop_order_id']];
            foreach ($edit->changes as $table => $rows) {
                foreach ($rows as $id => $change) {
                    if ($change[$to] !== null) continue;
                    foreach ($dependencies[$table] ?? [] as $child => $foreignKey) {
                        foreach ($current[$child] as $childId => $childRow) {
                            if (($childRow[$foreignKey] ?? null) == $id
                                && (!isset($edit->changes[$child][$childId]) || $edit->changes[$child][$childId][$to] !== null)) $this->conflict();
                        }
                    }
                }
            }
            // Delete children first, then restore parents before children. Keep audit logs.
            foreach (array_reverse($this->tables($section)) as $table) {
                foreach ($edit->changes[$table] ?? [] as $id => $change) {
                    if ($change[$to] === null) DB::table($table)->where('id', $id)->delete();
                }
            }
            foreach ($this->tables($section) as $table) {
                foreach ($edit->changes[$table] ?? [] as $id => $change) {
                    if ($change[$to] !== null) DB::table($table)->updateOrInsert(['id' => $id], $change[$to]);
                }
            }
            $edit->update(['undone_at' => $redo ? null : now()]);
            return $section === 'draft-invoices' ? (int) array_key_first($edit->changes['draft_invoices']) : null;
        });
    }

    private function conflict(): void
    {
        $this->reject('Không thể thực hiện vì dữ liệu đã được thay đổi sau đó. Phần chỉnh sửa của người khác được giữ nguyên.');
    }

    private function same(?array $left, ?array $right): bool
    {
        if ($left === null || $right === null) return $left === $right;
        unset($left['updated_at'], $right['updated_at']);
        return $left == $right;
    }

    private function reject(string $message): void
    {
        throw ValidationException::withMessages(['history' => $message]);
    }
}
