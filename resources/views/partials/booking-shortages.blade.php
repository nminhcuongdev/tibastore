@if(collect($shortages)->isNotEmpty())
    <div role="alert" style="grid-column:1/-1;background:#fff1ed;color:#9b261b;padding:12px;border-radius:6px;margin:8px 0">
        <strong>Đơn đã chốt cần kiểm tra lại theo lịch hàng hiện tại</strong>
        @foreach($shortages as $shortage)
            <div>
                <a href="{{ $shortage['url'] }}" style="color:#2563eb;text-decoration:underline">{{ $shortage['order_name'] }}</a>
                · Lấy {{ \Illuminate\Support\Carbon::parse($shortage['pickup'])->format('d/m/Y') }}
                · {{ $shortage['code'] }} · Size {{ $shortage['size'] }} thiếu {{ $shortage['shortage'] }}
            </div>
        @endforeach
    </div>
@endif
