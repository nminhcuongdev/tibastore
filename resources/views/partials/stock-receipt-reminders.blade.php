@php
    $receiptReminders = app(\App\Services\StockReceiptReminderService::class)->reminders();
    $receiptReminderCount = $receiptReminders['workshop']->count() + $receiptReminders['manual']->count();
@endphp
<button type="button" class="button secondary" data-open-stock-reminders aria-haspopup="dialog" aria-controls="stock-receipt-reminders">Nhắc nhập kho ({{ number_format($receiptReminderCount) }})</button>
<style>
#stock-receipt-reminders{border:1px solid #d8e4f5;border-radius:12px;padding:0;width:min(800px,calc(92vw / var(--desktop-display-scale, 1)));max-height:calc(85vh / var(--desktop-display-scale, 1));overflow:auto;color:#243b58;background:white}
#stock-receipt-reminders::backdrop{background:rgba(15,23,42,.4)}
.stock-reminder-head{padding:20px;background:#fff5e7;display:flex;gap:20px;justify-content:space-between;align-items:flex-start}
.stock-reminder-head h2{margin:0 0 8px;font-size:22px}.stock-reminder-head p{margin:0;line-height:1.6}
.stock-reminder-list{padding:18px;display:grid;gap:14px}.stock-reminder-item{border:1px solid #e6d9c5;border-radius:8px;padding:14px}
.stock-reminder-item p{margin:8px 0;white-space:normal}.stock-reminder-overdue{color:#b42318;font-weight:700}
</style>
<dialog id="stock-receipt-reminders" aria-labelledby="stock-receipt-reminder-title">
    <div class="stock-reminder-head">
        <div><h2 id="stock-receipt-reminder-title">Nhắc xác nhận hàng về</h2><p>Nếu hàng chưa về, mở dòng đặt hàng và sửa ngày dự kiến. Nếu đã về, kiểm từng size, lưu số thực nhận rồi bấm Đẩy lên kho.</p></div>
        <button type="button" class="button secondary" data-close-stock-reminders aria-label="Đóng nhắc nhập kho">×</button>
    </div>
    <div class="stock-reminder-list">
        @foreach ($receiptReminders['workshop'] as $receiptItem)
            <article class="stock-reminder-item">
                <strong>{{ $receiptItem->product_code }}{{ $receiptItem->order_name ? ' — '.$receiptItem->order_name : '' }}</strong>
                <p class="{{ $receiptItem->returned_date?->toDateString() < $receiptReminders['today'] && $receiptItem->returned_date ? 'stock-reminder-overdue' : '' }}">Dự kiến nhận: {{ $receiptItem->returned_date?->format('d/m/Y') ?? 'Chưa có ngày' }} · Chưa đẩy lên kho</p>
                <p>{{ $receiptItem->supplySourceLabel() }} · {{ $receiptItem->statusLabel() }} · Số lượng: {{ $receiptItem->quantity }}</p>
                @if ($receiptItem->size_note)<p>Size: {{ $receiptItem->size_note }}</p>@endif
                @if ($receiptItem->source)<p>Nhà cung cấp: {{ $receiptItem->source }}</p>@endif
                <a class="button secondary" href="{{ route('workshop-orders.index', ['focus' => $receiptItem->id]) }}#workshop-row-{{ $receiptItem->id }}">Đến dòng đặt hàng để nhập kho</a>
            </article>
        @endforeach
        @foreach ($receiptReminders['manual'] as $receiptItem)
            <article class="stock-reminder-item">
                <strong>{{ $receiptItem->product->code }} — Size {{ $receiptItem->product->size }}</strong>
                <p class="{{ $receiptItem->expected_receive_date->toDateString() < $receiptReminders['today'] ? 'stock-reminder-overdue' : '' }}">Dự kiến nhận: {{ $receiptItem->expected_receive_date->format('d/m/Y') }}</p>
                <p>Dự kiến nhập tay tại Kho · Số lượng: {{ $receiptItem->expected_receive_quantity }}. Sau khi nhập thực tế, cập nhật hoặc xóa phần dự kiến đã nhận để tránh tính trùng.</p>
                <a class="button secondary" href="{{ route('products.edit', $receiptItem->product) }}">Đến Kho để kiểm tra và nhập tay</a>
            </article>
        @endforeach
        @if ($receiptReminderCount === 0)<p>Không có hàng cần nhắc nhập kho.</p>@endif
    </div>
</dialog>
<script>
(() => {
    const button = document.querySelector('[data-open-stock-reminders]');
    const popup = document.getElementById('stock-receipt-reminders');
    button.addEventListener('click', () => popup.showModal());
    popup.querySelector('[data-close-stock-reminders]').addEventListener('click', () => popup.close());
    popup.addEventListener('close', () => button.focus());
    const dueOrders = @json($receiptReminders['workshop']->pluck('id')->values());
    if (dueOrders.length) window.addEventListener('load', () => {
        const key = 'workshop-arrival:' + @json($receiptReminders['today']);
        if (document.querySelector('dialog[open]')) return;
        try {
            const seen = JSON.parse(sessionStorage.getItem(key) || '[]');
            if (dueOrders.every(id => seen.includes(id))) return;
            sessionStorage.setItem(key, JSON.stringify([...new Set([...seen, ...dueOrders])]));
        } catch (_) {}
        popup.showModal();
    });
})();
</script>
