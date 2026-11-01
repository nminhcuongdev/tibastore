@php
    $invoiceLines = $order->items->map(fn ($item) => [
        'code' => $item->displayCode(), 'name' => $item->product?->name ?? '—',
        'image' => $item->product?->image_path ? asset('storage/'.$item->product->image_path) : null,
        'size' => $item->displaySize(), 'quantity' => $item->quantity,
        'price' => $item->rental_price ?? 0, 'days' => (float) ($item->rental_days ?? 1),
        'amount' => round(($item->rental_price ?? 0) * $item->quantity * (float) ($item->rental_days ?? 1)),
        'note' => $item->note,
    ]);
    if ($invoiceLines->isEmpty()) {
        $invoiceLines = $codeSummary->map(fn ($code) => array_merge($code, [
            'price' => null, 'days' => 1, 'amount' => $order->total_amount, 'note' => null,
        ]));
    }
@endphp
<div class="order-invoice-wrap">
    <table class="order-invoice-table" aria-label="Chi tiết sản phẩm đơn {{ $order->order_name }}">
        <colgroup><col style="width:5%"><col style="width:13%"><col style="width:21%"><col style="width:10%"><col style="width:6%"><col style="width:7%"><col style="width:12%"><col style="width:13%"><col style="width:13%"></colgroup>
        <thead><tr><th>STT</th><th>Mã / Ảnh</th><th>Tên sản phẩm</th><th>Size / ĐVT</th><th>SL</th><th>Số ngày</th><th>Đơn giá</th><th>Thành tiền</th><th>Ghi chú</th></tr></thead>
        <tbody>
        @foreach($invoiceLines as $line)
            <tr><td>{{ $loop->iteration }}</td><td><strong class="order-invoice-code">{{ $line['code'] }}</strong>
                @if($line['image'])
                    <button type="button" class="code-thumb has-image" data-zoom-src="{{ $line['image'] }}" data-zoom-alt="{{ $line['code'] }} - {{ $line['name'] }}" aria-label="Phóng to ảnh {{ $line['code'] }}"><img src="{{ $line['image'] }}" alt="{{ $line['code'] }}" loading="lazy"></button>
                @else <span class="order-invoice-no-image">Chưa có ảnh</span> @endif
            </td><td>{{ $line['name'] }}</td><td>{{ $line['size'] }}</td><td class="order-invoice-number">{{ number_format($line['quantity']) }}</td><td class="order-invoice-number">{{ rtrim(rtrim(number_format($line['days'], 2, '.', ''), '0'), '.') }}</td><td class="order-invoice-money">{{ $line['price'] === null ? '—' : number_format($line['price'], 0, ',', '.') }}</td><td class="order-invoice-money"><strong>{{ number_format($line['amount'], 0, ',', '.') }}</strong></td><td>{{ $line['note'] ?: '—' }}</td></tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr><td colspan="7">Tổng tiền hàng</td><td colspan="2" class="order-invoice-money">{{ number_format($order->total_amount, 0, ',', '.') }} đ</td></tr>
            <tr><td colspan="7">Chiết khấu @if($order->discount_percent)({{ (float) $order->discount_percent }}%)@endif</td><td colspan="2" class="order-invoice-money">− {{ number_format($order->discount_amount, 0, ',', '.') }} đ</td></tr>
            <tr data-expanded-compensation-row @if(!$order->compensation_amount) hidden @endif><td colspan="7">Bồi thường</td><td colspan="2" class="order-invoice-money" data-expanded-compensation>{{ number_format($order->compensation_amount, 0, ',', '.') }} đ</td></tr>
            <tr class="order-invoice-total"><td colspan="7">TỔNG ĐƠN</td><td colspan="2" class="order-invoice-money" data-cell="expanded-total">{{ number_format($order->total_with_compensation, 0, ',', '.') }} đ</td></tr>
            <tr><td colspan="7">Tiền ship</td><td colspan="2" class="order-invoice-money">{{ number_format($order->shipping_fee, 0, ',', '.') }} đ</td></tr>
            <tr><td colspan="7">Thanh toán lần 1</td><td colspan="2" class="order-invoice-money">{{ number_format($order->payment_1, 0, ',', '.') }} đ</td></tr>
            <tr><td colspan="7">Thanh toán lần 2</td><td colspan="2" class="order-invoice-money">{{ number_format($order->payment_2, 0, ',', '.') }} đ</td></tr>
            <tr class="order-invoice-total"><td colspan="7">CÒN LẠI</td><td colspan="2" class="order-invoice-money" data-expanded-remaining>{{ number_format($order->remaining, 0, ',', '.') }} đ</td></tr>
        </tfoot>
    </table>
</div>
