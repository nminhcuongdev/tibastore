<style>
#arrival-reminder-popup{border:1px solid #d8e4f5;border-radius:12px;padding:0;width:min(780px,92vw);max-height:85vh;color:#243b58;background:white}
#arrival-reminder-popup::backdrop{background:rgba(15,23,42,.4)}
.arrival-head{padding:20px;background:#fff0f5;display:flex;gap:20px;justify-content:space-between;align-items:flex-start}
.arrival-head h2{margin:0 0 8px;font-size:22px}.arrival-head p{margin:0;line-height:1.6}
.arrival-list{padding:18px;display:grid;gap:14px}.arrival-item{border:1px solid #e4d3dc;border-radius:8px;padding:14px;display:flex;gap:14px;align-items:flex-start}
.arrival-item img{width:72px;height:88px;object-fit:contain;border-radius:6px}.arrival-item p{margin:6px 0;white-space:pre-wrap;overflow-wrap:anywhere}.arrival-item a{display:inline-block;margin:6px 14px 0 0}.arrival-overdue{color:#b42318;font-weight:700}
</style>
<dialog id="arrival-reminder-popup" aria-labelledby="arrival-reminder-title">
    <div class="arrival-head">
        <div><h2 id="arrival-reminder-title">Nhắc hàng chưa về</h2>
        <p>Nhắc từ 2 ngày trước ngày khách lấy hàng, gồm Xưởng và Mua ngoài. Hàng quá hạn vẫn được nhắc đến khi đã về hoặc đơn đã gửi / kết thúc.</p></div>
        <button type="button" class="button secondary" data-close-arrival aria-label="Đóng nhắc hàng chưa về">×</button>
    </div>
    <div class="arrival-list">
        @forelse ($arrivalReminders as $reminder)
            @php $item = $reminder['item']; $customerOrder = $reminder['order']; @endphp
            <article class="arrival-item">
                @if ($item->image_url)<img src="{{ $item->image_url }}" alt="{{ $item->product_code }}">@endif
                <div>
                    <strong><a href="{{ route('workshop-orders.index', ['focus' => $item->id]) }}#workshop-row-{{ $item->id }}">{{ $customerOrder->order_name }} — {{ $item->product_code }}</a></strong>
                    <p class="{{ $reminder['overdue'] ? 'arrival-overdue' : '' }}">Khách lấy: {{ $customerOrder->pickup_date->format('d/m/Y') }}{{ $reminder['overdue'] ? ' · Đã quá ngày lấy' : '' }}</p>
                    <p>{{ $item->supplySourceLabel() }} · {{ $item->statusLabel() }} · Số lượng: {{ $item->quantity }}</p>
                    @if ($item->size_note)<p>Size: {{ $item->size_note }}</p>@endif
                    @if ($item->source)<p>Nhà cung cấp: {{ $item->source }}</p>@endif
                    <a class="button secondary" href="{{ route('workshop-orders.index', ['focus' => $item->id]) }}#workshop-row-{{ $item->id }}">Đến dòng đặt hàng</a>
                    <a class="button secondary" href="{{ route('orders.show', $customerOrder) }}">Đến đơn khách: {{ $customerOrder->order_name }}</a>
                </div>
            </article>
        @empty
            <p>Không có hàng chưa về cần nhắc.</p>
        @endforelse
        <p>Ghép theo Tên đơn và Mã hàng; dòng “Chưa tạo mã” ghép theo Tên đơn. Hãy nhập Tên đơn giống đơn khách để nhận nhắc.</p>
    </div>
</dialog>
<script>
(() => {
    const trigger = document.querySelector('[data-open-arrival-reminders]');
    const popup = document.getElementById('arrival-reminder-popup');
    trigger.addEventListener('click', () => popup.showModal());
    popup.querySelector('[data-close-arrival]').addEventListener('click', () => popup.close());
    popup.addEventListener('click', event => { if (event.target === popup) { const rect = popup.getBoundingClientRect(); if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) popup.close(); } });
    popup.addEventListener('close', () => trigger.focus());
})();
</script>
