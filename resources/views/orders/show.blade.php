<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thông tin đơn hàng {{ $order->order_name }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            background: #fff7f9;
            color: #3f2730;
            font-family: "Segoe UI", Arial, sans-serif;
            margin: 0;
            min-height: 100vh;
        }
        a { color: inherit; text-decoration: none; }
        .topbar {
            align-items: center;
            background: #fff;
            border-bottom: 1px solid #f5d9e2;
            display: flex;
            gap: 20px;
            justify-content: space-between;
            padding: 18px clamp(18px, 5vw, 56px);
        }
        .brand {
            color: #b63f68;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 28px;
            font-weight: 700;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .button {
            align-items: center;
            background: #be476f;
            border: 0;
            border-radius: 8px;
            color: #fff;
            cursor: pointer;
            display: inline-flex;
            font-weight: 800;
            min-height: 44px;
            padding: 10px 16px;
        }
        .button.secondary {
            background: #fff;
            border: 1px solid #ebc5d2;
            color: #8b2f4d;
        }
        .content { padding: 34px clamp(18px, 5vw, 56px) 56px; }
        .status {
            background: #fff;
            border: 1px solid #f0c7d3;
            border-left: 5px solid #c9577d;
            border-radius: 8px;
            color: #693143;
            margin: 0 auto 18px;
            max-width: 980px;
            padding: 12px 14px;
        }
        .receipt {
            background: #fff;
            border: 1px solid #f0d3dc;
            border-radius: 8px;
            box-shadow: 0 18px 45px rgba(117, 44, 69, .08);
            margin: 0 auto;
            max-width: 980px;
            overflow: hidden;
        }
        .receipt-head {
            background: #fff0f4;
            border-bottom: 1px solid #f0d3dc;
            display: grid;
            gap: 14px;
            grid-template-columns: 1fr auto;
            padding: 24px;
        }
        .eyebrow {
            color: #9d345a;
            font-size: 13px;
            font-weight: 900;
            letter-spacing: .08em;
            margin: 0 0 8px;
            text-transform: uppercase;
        }
        h1 {
            color: #6f253f;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(30px, 4vw, 46px);
            line-height: 1.08;
            margin: 0;
        }
        .order-id {
            color: #704252;
            font-weight: 800;
            text-align: right;
        }
        .status-badge {
            border-radius: 999px;
            display: inline-flex;
            font-size: 13px;
            font-weight: 900;
            margin-top: 8px;
            padding: 7px 11px;
        }
        .status-chua_cho_size { background: #f3eef1; color: #6b5560; }
        .status-da_in_file { background: #eaf2fd; color: #2f5fa6; }
        .status-da_in_giay { background: #eef0fb; color: #45489c; }
        .status-dang_soan { background: #f6ecfb; color: #7d3aa3; }
        .status-da_soan_xong { background: #e8f6f4; color: #1f7d6e; }
        .status-da_gui { background: #fff7d6; color: #8a5a00; }
        .status-da_tra_ve { background: #ffeede; color: #a85a18; }
        .status-thanh_cong { background: #e8f7ef; color: #247857; }
        .pay-coc { background: #f3eef1; color: #6b5560; }
        .pay-thanh_toan_1 { background: #eaf2fd; color: #2f5fa6; }
        .pay-thanh_toan_2 { background: #f6ecfb; color: #7d3aa3; }
        .pay-con_lai { background: #fff7d6; color: #8a5a00; }
        .status-form { margin: 0; }
        .status-select {
            border: 1px solid #f0d3dc;
            border-radius: 999px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 900;
            max-width: 200px;
            min-height: 44px;
            padding: 9px 12px;
        }
        .status-select:focus {
            border-color: #c9577d;
            box-shadow: 0 0 0 3px rgba(201, 87, 125, .16);
            outline: none;
        }
        .section { padding: 24px; }
        .section + .section { border-top: 1px solid #f7e3e9; }
        .section-title {
            color: #7a344c;
            font-size: 16px;
            font-weight: 900;
            margin: 0 0 14px;
            text-transform: uppercase;
        }
        .details {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        .detail {
            background: #fffafb;
            border: 1px solid #f2d3dc;
            border-radius: 8px;
            padding: 14px;
        }
        .label {
            color: #8b6672;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 6px;
        }
        .value {
            color: #3f2730;
            font-size: 17px;
            font-weight: 900;
            line-height: 1.35;
            overflow-wrap: anywhere;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border-bottom: 1px solid #f7e3e9;
            padding: 14px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #fff0f4;
            color: #81304c;
            font-size: 13px;
            letter-spacing: .02em;
            text-transform: uppercase;
        }
        .code {
            color: #a13b60;
            font-weight: 900;
        }
        .muted {
            color: #8b6672;
            font-size: 13px;
            line-height: 1.5;
        }
        .size-qty-list {
            color: #3f2730;
            font-weight: 800;
            line-height: 1.55;
            max-width: 320px;
        }
        .product-thumb {
            align-items: center;
            background: #fff4f7;
            border: 1px solid #f1cbd7;
            border-radius: 8px;
            color: #9d345a;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            height: 110px;
            justify-content: center;
            overflow: hidden;
            padding: 0;
            position: relative;
            width: 110px;
        }
        .product-thumb.has-image {
            cursor: zoom-in;
        }
        .product-thumb img {
            height: 100%;
            object-fit: cover;
            transition: transform .18s ease;
            width: 100%;
        }
        .product-thumb.has-image:hover,
        .product-thumb.has-image:focus {
            border-color: #c9577d;
            box-shadow: 0 10px 24px rgba(117, 44, 69, .18);
            outline: none;
        }
        .product-thumb.has-image:hover img,
        .product-thumb.has-image:focus img {
            transform: scale(1.12);
        }
        .image-lightbox {
            align-items: center;
            background: rgba(63, 39, 48, .72);
            cursor: zoom-out;
            display: none;
            inset: 0;
            justify-content: center;
            padding: 24px;
            position: fixed;
            z-index: 1000;
        }
        .image-lightbox.is-open {
            display: flex;
        }
        .image-lightbox img {
            background: #fff;
            border: 8px solid #fff;
            border-radius: 8px;
            box-shadow: 0 24px 70px rgba(0, 0, 0, .28);
            max-height: min(82vh, 760px);
            max-width: min(88vw, 760px);
            object-fit: contain;
        }
        .summary {
            display: flex;
            justify-content: flex-end;
            padding-top: 16px;
        }
        .summary-box {
            background: #fff4f7;
            border: 1px solid #f1cbd7;
            border-radius: 8px;
            min-width: 240px;
            padding: 16px;
        }
        .summary-box .value { font-size: 28px; }
        @media (max-width: 760px) {
            .topbar,
            .receipt-head,
            .details {
                grid-template-columns: 1fr;
            }
            .topbar {
                align-items: stretch;
                flex-direction: column;
            }
            .actions,
            .button {
                justify-content: center;
                width: 100%;
            }
            .order-id { text-align: left; }
            .section { padding: 18px; }
            table { min-width: 860px; }
            .table-shell { overflow-x: auto; }
        }
        @media print {
            body { background: #fff; }
            .topbar,
            .status,
            .no-print,
            .image-lightbox,
            .order-reminder-overlay {
                display: none !important;
            }
            .content { padding: 0; }
            .receipt {
                border: 0;
                box-shadow: none;
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <header class="topbar no-print">
        <a class="brand" href="{{ route('orders.index') }}">Tiba Boutique</a>
        <div class="actions">
            <form method="POST" action="{{ route('orders.status', $order) }}" class="status-form">
                @csrf
                @method('PATCH')
                <select name="status" class="status-select status-{{ $order->status }}" onchange="this.form.submit()" aria-label="Cập nhật trạng thái đơn">
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
            <form method="POST" action="{{ route('orders.payment-status', $order) }}" class="status-form">
                @csrf
                @method('PATCH')
                <select name="payment_status" class="status-select pay-{{ $order->payment_status }}" onchange="this.form.submit()" aria-label="Cập nhật trạng thái thanh toán">
                    @foreach ($paymentStatuses as $value => $label)
                        <option value="{{ $value }}" @selected($order->payment_status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
            <a class="button secondary" href="{{ route('orders.index') }}">Về danh sách</a>
            <a class="button secondary" href="{{ route('orders.edit', $order) }}">Sửa đơn</a>
            <button class="button" type="button" onclick="window.print()">In / chụp đơn</button>
        </div>
    </header>

    <main class="content">
        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        @php
            $displayItems = $order->items->isNotEmpty()
                ? $order->items
                : collect([(object) [
                    'product' => $order->product,
                    'quantity' => $order->quantity,
                ]]);
            $totalQuantity = $displayItems->sum('quantity');
            $groupedDisplayItems = $displayItems->groupBy(fn ($item) => $item->product?->code ?? 'N/A');
        @endphp

        <article class="receipt">
            <div class="receipt-head">
                <div>
                    <p class="eyebrow">Thông tin đơn hàng</p>
                    <h1>{{ $order->order_name }}</h1>
                </div>
                <div class="order-id">
                    <div>Mã đơn: #{{ $order->id }}</div>
                    <span class="status-badge status-{{ $order->status }}">{{ $order->statusLabel() }}</span>
                    <span class="status-badge pay-{{ $order->payment_status }}">{{ $order->paymentStatusLabel() }}</span>
                </div>
            </div>

            <section class="section">
                <h2 class="section-title">Thông tin chung</h2>
                <div class="details">
                    <div class="detail">
                        <div class="label">Người chốt</div>
                        <div class="value">{{ $order->closer_name }}</div>
                    </div>
                    <div class="detail">
                        <div class="label">Ngày tạo đơn</div>
                        <div class="value">{{ $order->created_at?->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="detail">
                        <div class="label">Cập nhật lần cuối</div>
                        <div class="value">{{ $order->updated_at?->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="detail">
                        <div class="label">Ngày lấy</div>
                        <div class="value">{{ $order->pickup_date?->format('d/m/Y') }}</div>
                    </div>
                    <div class="detail">
                        <div class="label">Ngày diễn</div>
                        <div class="value">{{ $order->event_date?->format('d/m/Y') }}</div>
                    </div>
                    <div class="detail">
                        <div class="label">Ngày trả</div>
                        <div class="value">{{ $order->return_date?->format('d/m/Y') }}</div>
                    </div>
                </div>
            </section>

            <section class="section">
                <h2 class="section-title">Sản phẩm trong đơn</h2>
                <div class="table-shell">
                    <table>
                        <thead>
                            <tr>
                                <th>Hình ảnh</th>
                                <th>Mã hàng</th>
                                <th>Tên hàng</th>
                                <th>Vải</th>
                                <th>Size - Số lượng</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($groupedDisplayItems as $code => $items)
                                @php
                                    $firstItem = $items->first();
                                    $product = $firstItem->product;
                                    $sizeQuantities = $items->groupBy(fn ($item) => $item->product?->size ?? 'N/A');
                                @endphp
                                <tr>
                                    <td>
                                        @if ($product?->image_path)
                                            <button class="product-thumb has-image" type="button" data-full-image="{{ asset('storage/' . $product->image_path) }}" aria-label="Phóng to ảnh {{ $product?->name }}">
                                                <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product?->name }}">
                                            </button>
                                        @else
                                            <div class="product-thumb">
                                                CHƯA CÓ ẢNH
                                            </div>
                                        @endif
                                    </td>
                                    <td class="code">{{ $code }}</td>
                                    <td>
                                        <div class="value">{{ $product?->name ?? 'Sản phẩm không còn tồn tại' }}</div>
                                        <div class="muted">Tồn hiện tại: {{ number_format($items->sum(fn ($item) => $item->product?->stock_quantity ?? 0)) }}</div>
                                    </td>
                                    <td>{{ $product?->fabric ?? 'N/A' }}</td>
                                    <td>
                                        <div class="size-qty-list">
                                            @foreach ($sizeQuantities as $size => $sizeItems)
                                                Size {{ $size }}: {{ number_format($sizeItems->sum('quantity')) }}@if (! $loop->last), @endif
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="summary">
                    <div class="summary-box">
                        <div class="label">Tổng số lượng</div>
                        <div class="value">{{ number_format($totalQuantity) }}</div>
                    </div>
                </div>
            </section>
        </article>
    </main>

    <div class="image-lightbox" data-image-lightbox aria-hidden="true">
        <img data-lightbox-image src="" alt="">
    </div>
    @include('orders.reminders-popup')
    <script>
        const lightbox = document.querySelector('[data-image-lightbox]');
        const lightboxImage = document.querySelector('[data-lightbox-image]');

        document.querySelectorAll('[data-full-image]').forEach(button => {
            button.addEventListener('click', () => {
                lightboxImage.src = button.dataset.fullImage;
                lightboxImage.alt = button.querySelector('img')?.alt || 'Ảnh sản phẩm';
                lightbox.classList.add('is-open');
                lightbox.setAttribute('aria-hidden', 'false');
            });
        });

        function closeLightbox() {
            lightbox.classList.remove('is-open');
            lightbox.setAttribute('aria-hidden', 'true');
            lightboxImage.src = '';
        }

        lightbox.addEventListener('click', closeLightbox);

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && lightbox.classList.contains('is-open')) {
                closeLightbox();
            }
        });
    </script>
</body>
</html>
