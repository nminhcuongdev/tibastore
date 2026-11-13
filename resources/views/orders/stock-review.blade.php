<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đơn thiếu tồn kho</title>
    @include('partials.favicon')
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f5f8ff; color: #1e293b; font: 15px/1.6 "Segoe UI", Arial, sans-serif; }
        main { max-width: 1300px; margin: 32px auto; padding: 0 20px; }
        a { color: #1e40af; }
        h1 { margin-bottom: 8px; }
        .summary, article, .empty { background: white; border: 1px solid #dce7f7; border-radius: 12px; padding: 20px; margin: 20px 0; }
        h2 { font-size: 19px; margin: 0 0 8px; }
        .meta { color: #64748b; margin-bottom: 14px; }
        .scroll { overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; min-width: 650px; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #e2e8f0; }
        th { background: #eff6ff; }
        .shortage { color: #b91c1c; font-weight: 700; }
        .button { display: inline-block; padding: 8px 16px; border-radius: 8px; background: #1e40af; color: white; text-decoration: none; }
    </style>
</head>
<body>
<div class="layout">
@include('partials.sidebar', ['active' => 'stock-review'])
<div class="page">
<main>
    <a href="{{ route('orders.index') }}">← Về danh sách đơn hàng</a>
    <h1>Đơn thiếu tồn kho</h1>
    <p>Các đơn đã lưu đang thiếu hàng theo mã, size và lịch thuê, dựa trên tồn thực tế đã nhập. Hàng đang đặt mua hoặc dự kiến về chưa được tính là đã có.</p>
    <div class="summary"><strong>{{ $reviews->count() }} đơn cần kiểm tra</strong> · {{ $reviews->sum(fn ($review) => $review['lines']->count()) }} dòng mã / size thiếu hàng</div>
    <p>Đơn trùng lịch có thể cùng xuất hiện vì dùng chung một lượng hàng thiếu; không cộng số thiếu giữa các đơn để ra số cần mua. Bỏ qua đơn đã hủy, đã trả về, đã kiểm và dòng chưa chốt size. Danh sách tự tính lại mỗi khi mở trang.</p>
    @forelse ($reviews as $review)
        @php($order = $review['order'])
        <article>
            <h2>#{{ $order->id }} · {{ $order->order_name }}</h2>
            <div class="meta">{{ \App\Models\Order::statuses()[$order->status] ?? $order->status }} · Người chốt: {{ $order->closer_name }} · Đi đơn: {{ ($order->dispatch_date ?? $order->pickup_date)?->format('d/m/Y') }} · Trả: {{ ($order->actual_return_date ?? $order->return_date)?->format('d/m/Y') }}</div>
            <div class="scroll">
                <table>
                    <thead><tr><th>Mã hàng</th><th>Tên hàng</th><th>Size</th><th>SL trong đơn</th><th>Có thể đáp ứng theo lịch</th><th>Thiếu</th><th>Tồn kho hiện tại</th></tr></thead>
                    <tbody>
                    @foreach ($review['lines'] as $line)
                        <tr><td>{{ $line['product']->code }}</td><td>{{ $line['product']->name }}</td><td>{{ $line['product']->size }}</td><td>{{ $line['quantity'] }}</td><td>{{ $line['available'] }}</td><td class="shortage">{{ $line['shortage'] }}</td><td>{{ $line['product']->stock_quantity }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <p><a class="button" href="{{ route('orders.edit', $order) }}">Mở đơn để kiểm tra</a></p>
        </article>
    @empty
        <div class="empty">Không có đơn thiếu tồn kho cần kiểm tra.</div>
    @endforelse
</main>
</div>
</div>
</body>
</html>
