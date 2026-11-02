@if (session('workshop_duplicate_warning'))
    <div role="alert" style="padding:16px;margin:14px 0;border:2px solid #d92d20;border-radius:9px;background:#fff0f0;color:#a51220">
        {{ session('workshop_duplicate_warning.message') }}
        <a href="{{ route('workshop-orders.index', ['focus' => session('workshop_duplicate_warning.focus')]) }}" style="color:inherit;font-weight:700">Kiểm tra dòng đặt hàng</a>
    </div>
@endif
