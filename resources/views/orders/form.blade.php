<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    @include('partials.favicon')
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mode === 'create' ? 'Tạo đơn hàng' : 'Sửa đơn hàng' }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            background: #f5f8ff;
            color: #1e293b;
            font-family: "Segoe UI", Arial, sans-serif;
            margin: 0;
            min-height: 100vh;
        }
        a { color: inherit; text-decoration: none; }
        .topbar {
            align-items: center;
            background: #fff;
            border-bottom: 1px solid #dce7f7;
            display: flex;
            gap: 20px;
            justify-content: space-between;
            padding: 18px clamp(18px, 5vw, 56px);
        }
        .brand {
            color: #1e40af;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 28px;
            font-weight: 700;
        }
        .content { padding: 36px clamp(18px, 5vw, 56px) 56px; }
        .heading { margin-bottom: 22px; }
        .heading h1 {
            color: #1e3a5f;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(32px, 4vw, 48px);
            line-height: 1.08;
            margin: 0 0 8px;
        }
        .heading p {
            color: #475569;
            margin: 0;
        }
        .form-shell {
            background: #fff;
            border: 1px solid #d8e4f5;
            border-radius: 8px;
            box-shadow: 0 18px 45px rgba(30, 64, 175, .08);
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            max-width: 1080px;
            padding: 24px;
        }
        .field {
            display: grid;
            gap: 7px;
        }
        .field.full { grid-column: 1 / -1; }
        .order-items {
            align-items: start;
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(440px, 1fr));
        }
        .order-item {
            background: #f8fbff;
            border: 1px solid #d8e4f5;
            border-radius: 8px;
            display: grid;
            gap: 12px;
            min-width: 0;
            padding: 14px;
            position: relative;
        }
        /* Keo tha de sap xep lai thu tu cac the ma hang cho de nhin. */
        .drag-handle {
            align-items: center;
            border-radius: 6px;
            color: #8ca3bf;
            cursor: grab;
            display: inline-flex;
            font-size: 15px;
            gap: 6px;
            justify-self: start;
            line-height: 1;
            padding: 4px 8px;
            user-select: none;
        }
        .drag-handle:hover { background: #eff6ff; color: #1e40af; }
        .drag-handle:active { cursor: grabbing; }
        .drag-handle .hint { font-size: 11px; font-weight: 700; }
        .order-item.is-dragging { opacity: .45; }
        .order-item.is-drop-target { box-shadow: 0 -3px 0 0 #1d4ed8; }
        .size-rows {
            display: grid;
            gap: 10px;
        }
        .size-row {
            align-items: end;
            display: grid;
            gap: 12px;
            grid-template-columns: 70px minmax(0, 1fr) 100px auto;
        }
        .size-row .button.danger {
            align-self: end;
        }
        .size-note-field {
            grid-column: 1 / -1;
        }
        .size-thumb {
            align-items: center;
            background: #e5efff;
            border: 1px solid #cbdcf5;
            border-radius: 8px;
            color: #1e40af;
            cursor: default;
            display: flex;
            font-family: inherit;
            font-size: 10px;
            font-weight: 800;
            height: 60px;
            justify-content: center;
            line-height: 1.2;
            overflow: hidden;
            padding: 4px;
            position: relative;
            text-align: center;
            width: 60px;
        }
        .size-thumb.has-image {
            cursor: zoom-in;
            padding: 0;
        }
        .size-thumb img {
            height: 100%;
            object-fit: cover;
            width: 100%;
        }
        .size-thumb.has-image:hover,
        .size-thumb.has-image:focus {
            border-color: #2563eb;
            box-shadow: 0 10px 24px rgba(30, 64, 175, .18);
            outline: none;
        }
        .image-lightbox {
            align-items: center;
            background: rgba(15, 23, 42, .72);
            cursor: zoom-out;
            display: none;
            inset: 0;
            justify-content: center;
            padding: 24px;
            position: fixed;
            z-index: 1000;
        }
        .image-lightbox.is-open { display: flex; }
        .image-lightbox img {
            background: #fff;
            border: 8px solid #fff;
            border-radius: 8px;
            box-shadow: 0 24px 70px rgba(0, 0, 0, .28);
            max-height: 90vh;
            max-width: 90vw;
            object-fit: contain;
        }
        .product-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .rental-row {
            align-items: end;
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
        }
        .rental-field {
            max-width: 220px;
        }
        .rental-row input[data-block-days] { width: 100px; }
        .block-subtotal {
            color: #334e75;
            font-weight: 800;
            padding-bottom: 12px;
        }
        .block-subtotal span {
            color: #1e40af;
        }
        .product-picker-cell {
            min-width: 0;
            position: relative;
        }
        .product-suggestions {
            background: #fff;
            border: 1px solid #d8e4f5;
            border-radius: 8px;
            box-shadow: 0 14px 36px rgba(30, 64, 175, .08);
            display: none;
            left: 0;
            margin-top: 6px;
            /* Moi dong cao hon vi co anh nen noi khung ra cho thay duoc nhieu ma. */
            max-height: 300px;
            overflow-y: auto;
            position: absolute;
            right: 0;
            top: 100%;
            z-index: 5;
        }
        .product-suggestion {
            align-items: center;
            background: transparent;
            border: 0;
            border-bottom: 1px solid #e7eef8;
            color: #1e293b;
            cursor: pointer;
            display: flex;
            gap: 10px;
            padding: 8px 12px;
            text-align: left;
            width: 100%;
        }
        .product-suggestion__info { min-width: 0; }
        .suggestion-thumb {
            align-items: center;
            background: #e5efff;
            border: 1px solid #cbdcf5;
            border-radius: 6px;
            color: #1e40af;
            display: flex;
            flex: 0 0 auto;
            font-size: 8px;
            font-weight: 800;
            height: 44px;
            justify-content: center;
            overflow: hidden;
            text-align: center;
            width: 44px;
        }
        .suggestion-thumb img { height: 100%; object-fit: cover; width: 100%; }
        .product-suggestion:hover { background: #eff6ff; }
        .product-code {
            color: #1e40af;
            font-weight: 900;
        }
        .product-name,
        .product-meta {
            color: #64748b;
            font-size: 13px;
        }
        .selected-product-info {
            background: #eff6ff;
            border: 1px solid #d8e4f5;
            border-radius: 8px;
            color: #475569;
            display: none;
            font-size: 13px;
            grid-column: 1 / -1;
            line-height: 1.45;
            padding: 10px 12px;
        }
        label {
            color: #334e75;
            font-size: 13px;
            font-weight: 800;
        }
        input,
        select,
        textarea {
            background: #fff;
            border: 1px solid #c3d4ed;
            border-radius: 8px;
            color: #1e293b;
            font-size: 15px;
            min-height: 44px;
            padding: 10px 13px;
            width: 100%;
        }
        .readonly-value {
            align-items: center;
            background: #eff6ff;
            border: 1px solid #d8e4f5;
            border-radius: 8px;
            color: #334e75;
            display: flex;
            font-weight: 900;
            min-height: 44px;
            padding: 10px 13px;
        }
        textarea {
            font-family: inherit;
            line-height: 1.5;
            resize: vertical;
        }
        input:focus,
        select:focus,
        textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .16);
            outline: none;
        }
        .error {
            color: #b4233f;
            font-size: 13px;
            font-weight: 700;
        }
        .qty-warning {
            color: #b4233f;
            font-size: 13px;
            font-weight: 700;
            grid-column: 1 / -1;
        }
        .stock-conflict {
            background: #fff5f5;
            border: 1px solid #f0b7c1;
            border-left: 5px solid #b4233f;
            border-radius: 8px;
            color: #b4233f;
            font-size: 14px;
            margin-bottom: 12px;
            padding: 12px 14px;
        }
        .row-error {
            display: none;
            grid-column: 1 / -1;
        }
        .actions,
        .items-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .actions {
            grid-column: 1 / -1;
            justify-content: flex-end;
            margin-top: 4px;
        }
        .items-actions { justify-content: flex-start; }
        .button {
            align-items: center;
            background: #1d4ed8;
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
            border: 1px solid #c3d4ed;
            color: #1e40af;
        }
        .button.danger {
            background: #fff;
            border: 1px solid #f0b7c1;
            color: #b4233f;
            min-height: 44px;
        }
        .button:disabled {
            cursor: not-allowed;
            opacity: .55;
        }
        @media (max-width: 860px) {
            .topbar,
            .form-shell,
            .order-items,
            .size-row {
                align-items: stretch;
                grid-template-columns: 1fr;
            }
            .topbar { flex-direction: column; }
            .actions { justify-content: stretch; }
            .button { justify-content: center; width: 100%; }
        }
        .flash {
            background: #fff;
            border: 1px solid #cbdcf5;
            border-left: 5px solid #2f9e6f;
            border-radius: 8px;
            color: #236c4f;
            margin-bottom: 18px;
            max-width: 1080px;
            padding: 12px 14px;
        }
        .flash.is-error {
            border-left-color: #b4233f;
            color: #b4233f;
        }
        #status:has(option[value="huy_don"]:checked) {
            background: #b4233f;
            border-color: #b4233f;
            color: #fff;
        }
    </style>
    @include('orders.status-colors')
    @include('partials.compact')
    @include('orders.entry-table-styles')
    @include('orders.editor-theme')
</head>
<body class="order-editor">
<div class="layout">
@include('partials.sidebar', ['active' => 'orders'])
<div class="page">

    <main class="content">
        <div class="heading order-editor-header">
            <div class="order-editor-brand"><img src="{{ route('brand.logo', [], false) }}?v={{ filemtime(resource_path('images/tiba-invoice-logo.png')) }}" alt="Logo Tiba Store" fetchpriority="high"><div><span class="order-editor-eyebrow">TIBA STORE</span><span class="order-editor-tagline">TRANG PHỤC BIỂU DIỄN</span></div></div>
            <div class="order-editor-title">
            <span class="order-editor-kicker">QUẢN LÝ ĐƠN HÀNG @if($mode === 'edit') · #{{ $order->id }} @endif</span>
            <h1>{{ $mode === 'create' ? 'Tạo đơn hàng mới' : 'Sửa thông tin đơn hàng' }}</h1>
            <p>Chọn nhiều sản phẩm từ kho, cập nhật lịch lấy, lịch diễn, lịch trả và trạng thái xử lý đơn.</p>
            </div>
            <div class="order-editor-history-slot">
                @include('partials.personal-history', ['historySection' => 'orders'])
            </div>
            <a class="order-editor-back" href="{{ route('orders.index') }}">Về danh sách <span aria-hidden="true">↗</span></a>
        </div>

        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="flash is-error">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="flash is-error">
                <strong>Chưa lưu được — vui lòng kiểm tra:</strong>
                <ul style="margin: 6px 0 0; padding-left: 18px;">
                    @foreach ($errors->all() as $error)
                        <li>@include('orders.stock-error-message', ['errorText' => $error])</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div id="draft_notice" class="flash" style="display:none; align-items:center; gap:12px;">
            <span>Đã khôi phục dữ liệu bạn nhập dở trước đó (tự lưu trong trình duyệt).</span>
            <button type="button" id="draft_clear" class="button secondary" style="min-height:36px; padding:8px 12px;">
                {{ $mode === 'create' ? 'Xoá nháp & nhập mới' : 'Bỏ nháp & lấy lại dữ liệu đã lưu' }}
            </button>
        </div>

        @php
            $oldItems = old('items');
            $initialItems = $oldItems !== null
                ? collect($oldItems)->map(fn ($item) => [
                    'product_id' => $item['product_id'] ?? '',
                    'quantity' => $item['quantity'] ?? 1,
                    'rental_price' => $item['rental_price'] ?? null,
                    'rental_days' => $item['rental_days'] ?? 1,
                    'size_pending' => ! empty($item['size_pending']),
                    'fulfillment_source' => $item['fulfillment_source'] ?? 'stock',
                    'purchase_plan' => $item['purchase_plan'] ?? null,
                    'note' => $item['note'] ?? null,
                ])->values()
                : ($orderItems->isNotEmpty()
                    ? $orderItems->map(fn ($item) => [
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'rental_price' => $item->rental_price,
                        'rental_days' => $item->rental_days ?? 1,
                        'size_pending' => (bool) $item->size_pending,
                        'fulfillment_source' => $item->fulfillment_source ?? 'stock',
                        'purchase_plan' => $item->purchase_plan,
                        'note' => $item->note,
                    ])->values()
                    : collect([[
                        'product_id' => $order->product_id,
                        'quantity' => $order->quantity ?? 1,
                        'rental_price' => null,
                        'size_pending' => false,
                        'note' => null,
                    ]])->filter(fn ($item) => ! empty($item['product_id'])));
        @endphp

        <form id="order_form" class="form-shell" method="POST" action="{{ $mode === 'create' ? route('orders.store') : route('orders.update', $order) }}">
            <div class="order-editor-section"><span>01</span><div><h2>Thông tin khách hàng</h2><p>Người đặt, liên hệ và ghi chú cho đơn hàng</p></div></div>
            @csrf
            <input type="hidden" name="_autosave_token" value="{{ old('_autosave_token', (string) \Illuminate\Support\Str::uuid()) }}">
            @if ($mode === 'edit')
                <input type="hidden" name="return_query" value="{{ old('return_query', request('return_query', '')) }}">
            @endif
            @if ($mode === 'edit')
                @method('PUT')
            @endif

            <div class="field">
                <label for="closer_name">Người chốt</label>
                <input id="closer_name" name="closer_name" type="text" value="{{ old('closer_name', $order->closer_name) }}" required>
                @error('closer_name') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="order_name">Tên đơn</label>
                <input id="order_name" name="order_name" type="text" value="{{ old('order_name', $order->order_name) }}" required>
                @error('order_name') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="phone">Số điện thoại</label>
                <input id="phone" name="phone" type="text" value="{{ old('phone', $order->phone) }}" placeholder="VD: 0901 234 567">
                @error('phone') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field full">
                <label for="note">Ghi chú đơn</label>
                <textarea id="note" name="note" rows="2" maxlength="2000" data-order-note
                    placeholder="Dặn dò chung của đơn, VD: khách hẹn lấy sau 17h, gửi kèm hóa đơn...">{{ old('note', $order->note) }}</textarea>
                @error('note') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field full">
                <label for="address">Địa chỉ</label>
                <input id="address" name="address" type="text" value="{{ old('address', $order->address) }}" placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành...">
                @error('address') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="region">Miền</label>
                <select id="region" name="region">
                    <option value="">Chưa xác định</option>
                    @foreach (\App\Models\Order::regions() as $value => $label)
                        <option value="{{ $value }}" @selected(old('region', $order->region) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('region') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="carrier">Nhà xe</label>
                <input id="carrier" name="carrier" type="text" value="{{ old('carrier', $order->carrier) }}" placeholder="VD: Nhà xe Hải Vân, Phương Trang...">
                @error('carrier') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="order-editor-section section-schedule"><span>02</span><div><h2>Lịch thuê & xử lý</h2><p>Ngày lấy, ngày diễn, ngày trả và trạng thái đơn</p></div></div>
            <div class="field">
                <label for="pickup_date">Ngày lấy</label>
                <input id="pickup_date" name="pickup_date" type="date" value="{{ old('pickup_date', $order->pickup_date?->format('Y-m-d')) }}" required>
                @error('pickup_date') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="event_date">Ngày diễn</label>
                <input id="event_date" name="event_date" type="date" value="{{ old('event_date', $order->event_date?->format('Y-m-d')) }}" required>
                @error('event_date') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="return_date">Ngày trả</label>
                <input id="return_date" name="return_date" type="date" value="{{ old('return_date', $order->return_date?->format('Y-m-d')) }}" required>
                @error('return_date') <div class="error">{{ $message }}</div> @enderror
                <label for="return_period">Buổi trả</label>
                <select id="return_period" name="return_period">
                    @foreach (\App\Models\Order::dayPeriods() as $value => $label)
                        <option value="{{ $value }}" @selected((old('return_period', $order->return_period) ?: 'sang') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('return_period') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="status">Trạng thái</label>
                <select id="status" name="status" required>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" class="status-{{ $value === \App\Models\Order::CHECKED_STATUS && $order->hasShortage() ? 'thanh_cong_thieu' : $value }}" @selected(old('status', $order->status ?? \App\Models\Order::DEFAULT_STATUS) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="order-editor-section section-payment"><span>03</span><div><h2>Thanh toán & chiết khấu</h2><p>Theo dõi tiền hàng, vận chuyển và các lần thanh toán</p></div></div>
            <div class="field">
                <label for="source">Nguồn hàng</label>
                <select id="source" name="source" required>
                    @foreach ($sources as $value => $label)
                        <option value="{{ $value }}" @selected(old('source', $order->source ?? \App\Models\Order::DEFAULT_SOURCE) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('source') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label>Tổng đơn (giá thuê × số lượng × số ngày − chiết khấu)</label>
                <div class="readonly-value" data-total-display>0</div>
            </div>

            <div class="field">
                <label for="shipping_fee">Tiền ship</label>
                <input id="shipping_fee" name="shipping_fee" type="number" step="1" value="{{ old('shipping_fee', $order->shipping_fee ?? 0) }}">
                <div class="muted">Nhập số âm nếu shop bù/giảm tiền ship cho khách.</div>
                @error('shipping_fee') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="payment_1">Thanh toán lần 1</label>
                <input id="payment_1" name="payment_1" type="number" min="0" step="1" value="{{ old('payment_1', $order->payment_1 ?? 0) }}">
                @error('payment_1') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="payment_2">Thanh toán lần 2</label>
                <input id="payment_2" name="payment_2" type="number" min="0" step="1" value="{{ old('payment_2', $order->payment_2 ?? 0) }}">
                @error('payment_2') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="discount_type">Chiết khấu</label>
                <select id="discount_type" name="discount_type">
                    <option value="vnd" @selected(old('discount_type', $order->discount_percent !== null ? 'percent' : 'vnd') === 'vnd')>Số tiền (VNĐ)</option>
                    <option value="percent" @selected(old('discount_type', $order->discount_percent !== null ? 'percent' : 'vnd') === 'percent')>Phần trăm (%)</option>
                </select>
                @error('discount_type') <div class="error">{{ $message }}</div> @enderror
                <label for="discount_amount">Mức chiết khấu</label>
                <input id="discount_amount" name="discount_amount" type="number" min="0" step="0.01" value="{{ old('discount_amount', $order->discount_percent ?? $order->discount_amount ?? 0) }}">
                <div class="muted" data-discount-display>Giảm trực tiếp vào tổng tiền hàng.</div>
                @error('discount_amount') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="order-editor-section section-products"><span>04</span><div><h2>Sản phẩm trong đơn</h2><p>Chọn mã, size và số lượng trực tiếp trong bảng</p></div></div>
            <div class="field full">
                <div id="stock_conflict" class="stock-conflict" style="display:none"></div>
                <div id="turnaround_warning" class="stock-conflict" role="alert" style="display:none"></div>
                <div class="order-entry-wrap"><table class="order-entry-table"><colgroup><col style="width:4%"><col><col style="width:23%"><col style="width:100px"><col style="width:76px"><col style="width:116px"><col style="width:4%"></colgroup><thead><tr><th>STT</th><th>Mã / Ảnh</th><th>Size · Số lượng · Ghi chú</th><th>Giá thuê</th><th>Số ngày</th><th>Thành tiền</th><th></th></tr></thead><tbody id="order_items" class="order-items"></tbody></table></div>
                <div class="items-actions">
                    <button id="add_item_button" class="button secondary" type="button">+ Thêm sản phẩm</button>
                </div>
                @error('items') <div class="error">@include('orders.stock-error-message', ['errorText' => $message])</div> @enderror
                @error('items.*.product_id') <div class="error">@include('orders.stock-error-message', ['errorText' => $message])</div> @enderror
                @error('items.*.quantity') <div class="error">@include('orders.stock-error-message', ['errorText' => $message])</div> @enderror
            </div>

            <div class="actions">
                <a class="button secondary" href="{{ route('orders.index') . ($mode === 'edit' && request('return_query') ? '?' . request('return_query') : '') }}">Hủy</a>
                <button class="button" type="submit">{{ $mode === 'create' ? 'Tạo đơn hàng' : 'Lưu thay đổi' }}</button>
            </div>
        </form>
    </main>
    <div class="image-lightbox" data-image-lightbox aria-hidden="true">
        <img data-lightbox-image src="" alt="">
    </div>
    @include('orders.reminders-popup')
    <script>
        const productOptions = @json($productOptions, JSON_UNESCAPED_UNICODE);
        const initialItems = @json($initialItems->values(), JSON_UNESCAPED_UNICODE);
        const availabilityUrl = @json(route('orders.availability'));
        let currentOrderId = @json($mode === 'edit' ? $order->id : null);
        const itemsContainer = document.getElementById('order_items');
        const addItemButton = document.getElementById('add_item_button');
        const orderForm = document.getElementById('order_form');
        const pickupDateInput = document.getElementById('pickup_date');
        const eventDateInput = document.getElementById('event_date');
        const returnDateInput = document.getElementById('return_date');
        const csrfToken = orderForm.querySelector('input[name="_token"]').value;
        const productAvailability = new Map();
        const strictAvailability = new Map();
        let sameDayReturns = {};
        let availabilityRequestId = 0;
        let itemIndex = 0;

        function findProductById(id) {
            for (const group of productOptions) {
                const item = group.items.find(product => Number(product.id) === Number(id));

                if (item) {
                    return { group, item };
                }
            }

            return null;
        }

        // Giá thuê ở kho theo mã hàng (các size cùng mã dùng chung); chỉ để làm giá mặc định.
        function codeRentalPrice(code) {
            const group = productOptions.find(item => item.code === code);

            if (! group || ! group.items.length) {
                return 0;
            }

            const withPrice = group.items.find(item => Number(item.rental_price) > 0);

            return Number((withPrice || group.items[0]).rental_price || 0);
        }

        const totalDisplay = document.querySelector('[data-total-display]');
        const discountInput = document.getElementById('discount_amount');
        const discountType = document.getElementById('discount_type');
        discountType.addEventListener('change', updateComputedTotal);
        discountInput.addEventListener('input', updateComputedTotal);
        const orderStatus = document.getElementById('status');
        orderStatus.addEventListener('change', updateComputedTotal);

        // Giá thuê nhập tại mã hàng áp cho mọi size của mã đó.
        // Thành tiền mỗi mã = giá thuê × tổng số lượng các size; tổng đơn = cộng dồn các mã.
        function updateComputedTotal() {
            let total = 0;

            itemsContainer.querySelectorAll('.order-item').forEach(block => {
                const rentalInput = block.querySelector('[data-block-rental]');
                const rental = Number(rentalInput ? rentalInput.value || 0 : 0);
                const daysInput = block.querySelector('[data-block-days]');
                const daysText = daysInput.value.trim().replace(',', '.');
                const days = Number(daysText || 1);
                daysInput.setCustomValidity(/^\d+(\.\d{1,2})?$/.test(daysText) && days > 0 && days <= 3650 ? '' : 'Nhập số ngày lớn hơn 0, tối đa 3650; ví dụ 1,5 hoặc 2,5 (tối đa 2 chữ số thập phân).');
                let blockAmount = 0;
                let blockQuantity = 0;

                block.querySelectorAll('.size-row').forEach(sizeRow => {
                    const productId = sizeRow.querySelector('[data-product-id]').value;
                    const quantity = Number(sizeRow.querySelector('[data-quantity]').value || 0);
                    const rentalHidden = sizeRow.querySelector('[data-rental]');

                    // Ghi giá thuê của mã vào input ẩn từng size để gửi lên server.
                    if (rentalHidden) {
                        rentalHidden.value = String(rental);
                    }
                    sizeRow.querySelector('[data-rental-days]').value = String(days);

                    if (productId && quantity > 0) {
                        blockQuantity += quantity;
                        blockAmount += Math.round(rental * quantity * days);
                    }
                });

                block.querySelector('[data-block-quantity]').textContent = blockQuantity.toLocaleString('vi-VN');
                const subtotal = blockAmount;
                const subtotalEl = block.querySelector('[data-block-subtotal]');

                if (subtotalEl) {
                    subtotalEl.textContent = subtotal.toLocaleString('vi-VN');
                }

                total += subtotal;
            });

            if (totalDisplay) {
                const value = Number(discountInput.value || 0);
                const percent = discountType.value === 'percent';
                const discount = percent ? Math.round(total * value / 100) : value;
                discountInput.step = percent ? '0.01' : '1';
                discountInput.max = String(percent ? 100 : total);
                discountInput.setCustomValidity((percent ? value > 100 : discount > total) ? (percent ? 'Chiết khấu tối đa 100%.' : 'Chiết khấu không được vượt quá tổng tiền hàng.') : '');
                document.querySelector('[data-discount-display]').textContent = `Số tiền giảm: ${discount.toLocaleString('vi-VN')} VNĐ${percent ? ' (làm tròn đến đồng)' : ''}.`;
                totalDisplay.textContent = (orderStatus.value === 'huy_don' ? 0 : Math.max(0, total - discount)).toLocaleString('vi-VN');
            }
            updateTurnaroundWarning();
        }

        function updateTurnaroundWarning() {
            const box = document.getElementById('turnaround_warning');
            if (!box) return;
            box.replaceChildren();
            const quantities = new Map();
            const pendingCodes = new Set();
            itemsContainer.querySelectorAll('.size-row').forEach(row => {
                const id = Number(row.querySelector('[data-product-id]').value);
                if (isPendingRow(row)) {
                    const selected = findProductById(id);
                    if (selected) pendingCodes.add(selected.group.code);
                }
                quantities.set(id, (quantities.get(id) || 0) + Number(row.querySelector('[data-quantity]').value || 0));
            });
            const warnings = [];
            const warnedCodes = new Set();
            const allReturns = Object.values(sameDayReturns).flat();
            quantities.forEach((quantity, id) => {
                const selected = findProductById(id);
                if (!selected || quantity <= 0 || orderStatus.value === 'huy_don') return;
                const code = selected.group.code;
                const uncertain = allReturns.filter(item => item.code === code && (item.size_pending || pendingCodes.has(code)));
                if (uncertain.length && !warnedCodes.has(code)) {
                    warnedCodes.add(code);
                    warnings.push({ selected, message: `⚠ Mã ${code} có đơn chưa chốt size trùng ngày trả–lấy ${pickupDateInput.value}. Cần xác nhận size và đòi đồ về sớm từ: ${uncertain.map(item => `${item.order_name} (${item.quantity} cái${item.size_pending ? ', chưa chốt size' : ''})`).join('; ')}. Đây là cảnh báo theo mã, chưa xác định số thiếu theo size và không thay đổi tồn kho.` });
                }
                if (pendingCodes.has(code)) return;
                const returns = (sameDayReturns[id] || []).filter(item => !item.size_pending);
                const shortage = quantity - (strictAvailability.get(id) || 0);
                if (shortage <= 0 || !returns.length) return;
                warnings.push({ selected, message: `⚠ Thiếu ${shortage.toLocaleString('vi-VN')} cái mã ${code} — size ${selected.item.size} nếu đồ chưa về kịp ngày lấy ${pickupDateInput.value}. Cần đòi về sớm từ: ${returns.map(item => `${item.order_name} (${item.quantity} cái)`).join('; ')}. Bạn vẫn có thể lưu đơn; hệ thống nhắc khi mở website trước ngày lấy 1 ngày.` });
            });
            warnings.forEach(({ selected, message }) => {
                const line = document.createElement('div');
                line.style.cssText = 'display:flex;align-items:center;gap:12px;margin:8px 0';
                const imageUrl = selected.item.image_url || (selected.group.items.find(item => item.image_url) || {}).image_url;
                if (imageUrl) {
                    const link = document.createElement('a');
                    link.href = imageUrl;
                    link.target = '_blank';
                    link.rel = 'noopener';
                    link.setAttribute('aria-label', `Xem ảnh mã ${selected.group.code}`);
                    const image = document.createElement('img');
                    image.src = imageUrl;
                    image.alt = `Mã ${selected.group.code} — size ${selected.item.size}`;
                    image.style.cssText = 'width:72px;height:88px;object-fit:cover;border-radius:8px';
                    link.appendChild(image);
                    line.appendChild(link);
                } else {
                    const placeholder = document.createElement('span');
                    placeholder.textContent = 'Chưa có ảnh';
                    placeholder.style.cssText = 'min-width:72px;font-size:12px';
                    line.appendChild(placeholder);
                }
                const text = document.createElement('span');
                text.textContent = message;
                line.appendChild(text);
                box.appendChild(line);
            });
            box.style.display = box.childElementCount ? 'block' : 'none';
        }

        function stockLimit(product) {
            return Number(productAvailability.get(Number(product.id)) ?? product.stock_quantity ?? 0);
        }

        function allProductIds() {
            return productOptions.flatMap(group => group.items.map(product => product.id));
        }

        function resetAvailabilityToCurrentStock() {
            productAvailability.clear();

            productOptions.forEach(group => {
                group.items.forEach(product => {
                    productAvailability.set(Number(product.id), Number(product.stock_quantity || 0));
                });
            });
        }

        function datesReadyForAvailability() {
            const pickupDate = pickupDateInput.value;
            const eventDate = eventDateInput.value;
            const returnDate = returnDateInput.value;

            if (! pickupDate || ! returnDate) {
                return false;
            }

            if (eventDate && eventDate < pickupDate) {
                return false;
            }

            if (eventDate && returnDate < eventDate) {
                return false;
            }

            if (! eventDate && returnDate < pickupDate) {
                return false;
            }

            return true;
        }

        async function refreshAvailability(reason = 'init') {
            const requestId = ++availabilityRequestId;

            if (! datesReadyForAvailability()) {
                strictAvailability.clear();
                sameDayReturns = {};
                updateTurnaroundWarning();
                resetAvailabilityToCurrentStock();
                refreshProductChoices();
                return;
            }

            try {
                const response = await fetch(availabilityUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        pickup_date: pickupDateInput.value,
                        event_date: eventDateInput.value,
                        return_date: returnDateInput.value,
                        return_period: document.getElementById('return_period').value,
                        product_ids: allProductIds(),
                        exclude_order_id: currentOrderId,
                    }),
                });

                if (! response.ok) {
                    throw new Error('availability request failed');
                }

                const data = await response.json();

                if (requestId !== availabilityRequestId) {
                    return;
                }

                productAvailability.clear();
                strictAvailability.clear();
                Object.entries(data.strict_availability || {}).forEach(([id, quantity]) => strictAvailability.set(Number(id), Number(quantity)));
                sameDayReturns = data.same_day_returns || {};
                Object.entries(data.availability || {}).forEach(([productId, quantity]) => {
                    productAvailability.set(Number(productId), Number(quantity || 0));
                });
                refreshProductChoices();
                itemsContainer.querySelectorAll('.size-row').forEach(validateSizeRow);
                updateTurnaroundWarning();

                // Khi người dùng đổi ngày: nếu tồn dự kiến mới làm dòng nào vượt (gối đơn khác),
                // tự điều chỉnh về tối đa và tổng hợp cảnh báo. Không đụng lúc mới mở form.
                if (reason === 'date-change') {
                    const conflicts = [];

                    itemsContainer.querySelectorAll('.size-row').forEach(sizeRow => {
                        if (isPendingRow(sizeRow)) {
                            return;
                        }

                        const selected = findProductById(sizeRow.querySelector('[data-product-id]').value);

                        if (! selected) {
                            return;
                        }

                        const quantityInput = sizeRow.querySelector('[data-quantity]');
                        const limit = remainingStockFor(sizeRow, selected.item);
                        const value = Number(quantityInput.value || 0);

                        if (value > limit) {
                            conflicts.push(`${selected.group.code} - size ${selected.item.size}: ${value} → tối đa ${limit}`);
                            quantityInput.value = limit;
                            updateQuantityLimit(sizeRow);
                        }
                    });

                    if (conflicts.length > 0) {
                        showStockConflict(conflicts);
                    } else {
                        clearStockConflict();
                    }

                    updateComputedTotal();
                }
            } catch (error) {
                if (requestId !== availabilityRequestId) {
                    return;
                }

                strictAvailability.clear();
                sameDayReturns = {};
                updateTurnaroundWarning();
                resetAvailabilityToCurrentStock();
                refreshProductChoices();
            }
        }

        function isPendingRow(sizeRow) {
            const pendingInput = sizeRow.querySelector('[data-size-pending]');
            return pendingInput && pendingInput.value === '1';
        }

        // Chỉ chống trùng size TRONG CÙNG MỘT KHỐI. Khác khối (kể cả cùng mã) được
        // phép trùng size — để lên đơn cùng mã với 2 giá khác nhau (thuê bộ / thuê lẻ).
        function selectedProductIdsInBlock(block, exceptSizeRow = null) {
            return new Set(Array.from(block.querySelectorAll('.size-row'))
                .filter(sizeRow => sizeRow !== exceptSizeRow)
                // Dòng "chưa chốt" dùng mã đại diện — không tính là đã chọn size.
                .filter(sizeRow => ! isPendingRow(sizeRow))
                .map(sizeRow => sizeRow.querySelector('[data-product-id]').value)
                .filter(Boolean)
                .map(String));
        }

        function availableProductsForSizeRow(group, sizeRow, selectedProductId = null) {
            const block = sizeRow.closest('.order-item');
            const selectedIds = selectedProductIdsInBlock(block, sizeRow);

            return group.items.filter(product => {
                const isCurrentSelection = selectedProductId && Number(product.id) === Number(selectedProductId);

                return isCurrentSelection || (! selectedIds.has(String(product.id)) && (block.querySelector('[data-supply-choice]').value !== 'stock' || stockLimit(product) > 0));
            });
        }

        function availableProductsForNewSize(block, code) {
            const group = productOptions.find(item => item.code === code);

            if (! group) {
                return [];
            }

            const selectedIds = block ? selectedProductIdsInBlock(block) : new Set();

            return group.items.filter(product => ! selectedIds.has(String(product.id)) && (block?.querySelector('[data-supply-choice]').value !== 'stock' || stockLimit(product) > 0));
        }

        function updateAddSizeButton(block) {
            const addSizeButton = block.querySelector('[data-add-size]');

            if (! addSizeButton) {
                return;
            }

            addSizeButton.disabled = ! block.dataset.productCode
                || availableProductsForNewSize(block, block.dataset.productCode).length === 0;
        }

        function updateAllAddSizeButtons() {
            itemsContainer.querySelectorAll('.order-item').forEach(updateAddSizeButton);
        }

        function showRowError(block, message) {
            const rowError = block.querySelector('[data-row-error]');
            rowError.style.display = 'block';
            rowError.textContent = message;
        }

        function clearRowError(block) {
            const rowError = block.querySelector('[data-row-error]');
            rowError.style.display = 'none';
            rowError.textContent = '';
        }

        function showBlockInfo(block, message) {
            const selectedInfo = block.querySelector('[data-selected-info]');
            selectedInfo.style.display = 'block';
            selectedInfo.textContent = message;
        }

        function setRowQtyWarning(sizeRow, message) {
            const el = sizeRow.querySelector('[data-qty-warning]');

            if (! el) {
                return;
            }

            if (message) {
                el.textContent = message;
                el.style.display = 'block';
            } else {
                el.textContent = '';
                el.style.display = 'none';
            }
        }

        const stockConflictBox = document.getElementById('stock_conflict');

        function showStockConflict(lines) {
            if (! stockConflictBox) {
                return;
            }

            stockConflictBox.innerHTML = '';
            const title = document.createElement('strong');
            title.textContent = 'Đổi ngày làm một số mã vượt tồn dự kiến (do gối với đơn khác) — đã tự điều chỉnh về tối đa:';
            const list = document.createElement('ul');
            list.style.margin = '6px 0 0';
            list.style.paddingLeft = '18px';

            lines.forEach(line => {
                const li = document.createElement('li');
                li.textContent = line;
                list.appendChild(li);
            });

            stockConflictBox.append(title, list);
            stockConflictBox.style.display = 'block';
        }

        function clearStockConflict() {
            if (! stockConflictBox) {
                return;
            }

            stockConflictBox.style.display = 'none';
            stockConflictBox.innerHTML = '';
        }

        // Tổng số lượng của CÙNG một sản phẩm (mã-size) ở các dòng khác, tính trên mọi khối.
        // Cùng mã-size nhưng khác giá thuê là 2 dòng riêng, kho vẫn phải trừ cộng dồn.
        function quantityUsedElsewhere(sizeRow, productId) {
            if (! productId) {
                return 0;
            }

            let used = 0;

            itemsContainer.querySelectorAll('.size-row').forEach(otherRow => {
                if (otherRow === sizeRow || isPendingRow(otherRow)) {
                    return;
                }

                if (String(otherRow.querySelector('[data-product-id]').value) !== String(productId)) {
                    return;
                }

                used += Number(otherRow.querySelector('[data-quantity]').value || 0);
            });

            return used;
        }

        // Tồn còn dùng được cho dòng này = tồn dự kiến − số lượng cùng mã-size đã đặt ở dòng khác.
        function remainingStockFor(sizeRow, product) {
            return Math.max(0, stockLimit(product) - quantityUsedElsewhere(sizeRow, product.id));
        }

        function updateQuantityLimit(sizeRow) {
            const quantityInput = sizeRow.querySelector('[data-quantity]');
            const productId = sizeRow.querySelector('[data-product-id]').value;
            const selected = findProductById(productId);

            quantityInput.setCustomValidity('');
            quantityInput.removeAttribute('max');

            // Dòng "chưa chốt" không giữ kho theo size nên không giới hạn số lượng theo tồn.
            if (isPendingRow(sizeRow) || sizeRow.closest('.order-item').querySelector('[data-supply-choice]').value !== 'stock') {
                setRowQtyWarning(sizeRow, '');
                return true;
            }

            if (! selected) {
                setRowQtyWarning(sizeRow, '');
                return true;
            }

            const usedElsewhere = quantityUsedElsewhere(sizeRow, productId);
            const limit = remainingStockFor(sizeRow, selected.item);
            const value = Number(quantityInput.value || 0);
            quantityInput.max = limit;

            // Vượt tồn dự kiến: không tự cắt, mà báo rõ ngay tại dòng và chặn lưu.
            if (value > limit) {
                const usedNote = usedElsewhere > 0
                    ? ` (đã trừ ${usedElsewhere.toLocaleString('vi-VN')} của cùng mã-size ở dòng khác)`
                    : '';
                quantityInput.setCustomValidity(`Số lượng vượt tồn dự kiến (tối đa ${limit}).`);
                setRowQtyWarning(sizeRow, `⚠ Vượt tồn dự kiến — tối đa ${limit.toLocaleString('vi-VN')}${usedNote}.`);
                return false;
            }

            if (value < 1) {
                quantityInput.setCustomValidity('Số lượng phải lớn hơn 0.');
                setRowQtyWarning(sizeRow, '');
                return false;
            }

            setRowQtyWarning(sizeRow, '');
            return true;
        }

        function validateSizeRow(sizeRow) {
            const productId = sizeRow.querySelector('[data-product-id]').value;

            if (! productId) {
                return false;
            }

            return updateQuantityLimit(sizeRow);
        }

        function renumberRows() {
            let index = 0;
            itemsContainer.querySelectorAll('[data-block-number]').forEach((number, i) => number.textContent = i + 1);

            itemsContainer.querySelectorAll('.size-row').forEach(sizeRow => {
                sizeRow.querySelector('[data-product-id]').name = `items[${index}][product_id]`;
                sizeRow.querySelector('[data-quantity]').name = `items[${index}][quantity]`;
                const sourceInput = sizeRow.querySelector('[data-fulfillment-source]');
                sourceInput.name = `items[${index}][fulfillment_source]`;
                sourceInput.value = sizeRow.closest('.order-item').querySelector('[data-supply-choice]').value;
                const planInput = sizeRow.querySelector('[data-purchase-plan]');
                planInput.name = `items[${index}][purchase_plan]`;
                const plan = sizeRow.closest('.order-item').purchasePlan;
                planInput.value = sourceInput.value === 'stock' || !plan ? '' : JSON.stringify(plan);
                sizeRow.querySelector('[data-rental-days]').name = `items[${index}][rental_days]`;
                const rentalHidden = sizeRow.querySelector('[data-rental]');
                if (rentalHidden) {
                    rentalHidden.name = `items[${index}][rental_price]`;
                }
                const pendingHidden = sizeRow.querySelector('[data-size-pending]');
                if (pendingHidden) {
                    pendingHidden.name = `items[${index}][size_pending]`;
                }
                const noteInput = sizeRow.querySelector('[data-note]');
                if (noteInput) {
                    noteInput.name = `items[${index}][note]`;
                }
                index += 1;
            });
        }

        // Anh nho cua ma hang: lay anh cua size dau tien co anh.
        function buildSuggestionThumb(group) {
            const imageUrl = (group.items.find(product => product.image_url) || {}).image_url || null;

            if (!imageUrl) {
                const empty = document.createElement('div');
                empty.className = 'suggestion-thumb';
                empty.textContent = 'CHƯA CÓ ẢNH';
                return empty;
            }

            const box = document.createElement('div');
            box.className = 'suggestion-thumb has-image';

            const img = document.createElement('img');
            img.src = imageUrl;
            img.alt = group.code;
            box.appendChild(img);

            return box;
        }

        function renderSuggestions(block, keyword = '') {
            const suggestions = block.querySelector('[data-suggestions]');
            const normalizeSearch = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd').toLowerCase().trim();
            const normalizedKeyword = normalizeSearch(keyword);
            const category = block.querySelector('[data-category]').value;
            const matches = productOptions.filter(group => {
                const hasKeyword = normalizeSearch(group.code + ' ' + group.name + ' ' + group.items.map(item => item.category || '').join(' ')).includes(normalizedKeyword);
                const hasCategory = !category || group.items.some(item => item.category === category);

                // Mã mới tạo hoặc hết tồn vẫn cần tìm được để chọn nguồn od.
                return hasKeyword && hasCategory;
            }).slice(0, 20);

            suggestions.innerHTML = '';

            if (matches.length === 0) {
                suggestions.style.display = 'none';
                return;
            }

            matches.forEach(group => {
                const button = document.createElement('button');
                const info = document.createElement('div');
                const code = document.createElement('div');
                const name = document.createElement('div');
                const meta = document.createElement('div');

                button.type = 'button';
                button.className = 'product-suggestion';
                info.className = 'product-suggestion__info';
                code.className = 'product-code';
                code.textContent = group.code;
                name.className = 'product-name';
                name.textContent = group.name;
                meta.className = 'product-meta';
                const stockedSizes = group.items.filter(product => stockLimit(product) > 0).length;
                meta.textContent = stockedSizes ? `${stockedSizes}/${group.items.length} size còn tồn`
                    : 'Chưa có tồn khả dụng — chọn nguồn od hoặc nhập kho trước';
                info.append(code, name, meta);
                button.append(buildSuggestionThumb(group), info);
                button.addEventListener('click', () => selectProductCode(block, group.code));
                suggestions.appendChild(button);
            });

            suggestions.style.display = 'block';
            const rect = block.querySelector('[data-search]').getBoundingClientRect();
            const width = Math.min(360, window.innerWidth - 20);
            suggestions.style.width = width + 'px';
            suggestions.style.left = Math.max(10, Math.min(rect.left, window.innerWidth - width - 10)) + 'px';
            const below = window.innerHeight - rect.bottom;
            const above = below < 250 && rect.top > below;
            suggestions.style.maxHeight = Math.max(120, Math.min(320, (above ? rect.top : below) - 20)) + 'px';
            suggestions.style.top = (above ? Math.max(10, rect.top - suggestions.offsetHeight - 6) : rect.bottom + 6) + 'px';
        }

        function clearSizeRows(block) {
            block.querySelector('[data-size-rows]').innerHTML = '';
        }

        function populateSizeSelect(sizeRow, selectedProductId = null) {
            const block = sizeRow.closest('.order-item');
            const group = productOptions.find(item => item.code === block.dataset.productCode);
            const sizeSelect = sizeRow.querySelector('[data-size]');
            const productIdInput = sizeRow.querySelector('[data-product-id]');
            const pendingInput = sizeRow.querySelector('[data-size-pending]');
            const isPending = pendingInput && pendingInput.value === '1';
            const currentSelection = selectedProductId ?? productIdInput.value;

            sizeSelect.innerHTML = '<option value="">Chọn size</option>';
            sizeSelect.disabled = ! group;

            if (! group) {
                productIdInput.value = '';
                return;
            }

            const availableProducts = availableProductsForSizeRow(group, sizeRow, currentSelection);

            availableProducts.forEach(product => {
                const option = document.createElement('option');
                option.value = product.id;
                option.textContent = `${product.size} | Tồn dự kiến: ${stockLimit(product)}`;
                option.selected = ! isPending && Number(product.id) === Number(currentSelection);
                sizeSelect.appendChild(option);
            });

            // Luôn có tùy chọn "Chưa chốt" — cập nhật size sau, số lượng vẫn được tính.
            const pendingOption = document.createElement('option');
            pendingOption.value = 'pending';
            pendingOption.textContent = 'Chưa chốt (cập nhật size sau)';
            pendingOption.selected = isPending;
            sizeSelect.appendChild(pendingOption);

            if (isPending) {
                // Đại diện = biến thể đầu tiên của mã, chỉ để giữ FK/nhóm mã.
                productIdInput.value = group.items[0].id;
            } else if (currentSelection && availableProducts.some(product => Number(product.id) === Number(currentSelection))) {
                productIdInput.value = currentSelection;
            } else {
                productIdInput.value = '';
            }

            updateSizeImage(sizeRow);
        }

        function updateSizeImage(sizeRow) {
            const thumb = sizeRow.querySelector('[data-size-thumb]');
            const img = sizeRow.querySelector('[data-size-thumb-img]');
            const empty = sizeRow.querySelector('[data-size-thumb-empty]');
            const selected = findProductById(sizeRow.querySelector('[data-product-id]').value);
            const imageUrl = ! isPendingRow(sizeRow) && selected && selected.item.image_url ? selected.item.image_url : null;

            if (imageUrl) {
                img.src = imageUrl;
                img.style.display = '';
                empty.style.display = 'none';
                thumb.classList.add('has-image');
                thumb.dataset.fullImage = imageUrl;
                thumb.disabled = false;
            } else {
                img.removeAttribute('src');
                img.style.display = 'none';
                empty.style.display = '';
                thumb.classList.remove('has-image');
                delete thumb.dataset.fullImage;
                thumb.disabled = true;
            }
        }

        function selectProductCode(block, code) {
            const group = productOptions.find(item => item.code === code);
            const searchInput = block.querySelector('[data-search]');
            const suggestions = block.querySelector('[data-suggestions]');
            const selectedInfo = block.querySelector('[data-selected-info]');

            searchInput.value = group ? `${group.code} - ${group.name}` : code;
            block.dataset.productCode = group ? group.code : '';
            suggestions.style.display = 'none';
            selectedInfo.style.display = 'none';
            selectedInfo.textContent = '';
            clearRowError(block);

            clearSizeRows(block);
            addSizeRow(block);

            // Chọn mã mới: lấy giá thuê ở kho làm mặc định (vẫn cho sửa lại).
            const rentalInput = block.querySelector('[data-block-rental]');
            if (rentalInput) {
                rentalInput.value = codeRentalPrice(group ? group.code : '');
            }

            updateAddSizeButton(block);
            renderSelectedInfo(block);
            updateComputedTotal();
        }

        function renderSelectedInfo(block) {
            const selectedInfo = block.querySelector('[data-selected-info]');
            const group = productOptions.find(item => item.code === block.dataset.productCode);
            selectedInfo.textContent = group ? group.name : 'Chưa chọn sản phẩm';
            if (group && !group.items.some(product => stockLimit(product) > 0)) {
                selectedInfo.textContent += ' — Chưa có tồn khả dụng. Chọn nguồn Cần od – Xưởng / Mua ngoài, hoặc nhập kho trước khi dùng Sẵn kho.';
            }
            selectedInfo.style.display = 'block';
            const image = block.querySelector('[data-code-preview]');
            const product = group && group.items.find(item => item.image_url);
            image.hidden = !product;
            const thumb = image.closest('button');
            thumb.disabled = !product;
            if (product) { image.src = product.image_url; image.alt = group.name; thumb.dataset.fullImage = product.image_url; }
            else { image.removeAttribute('src'); delete thumb.dataset.fullImage; }
            block.dispatchEvent(new Event('product-updated'));
        }

        function refreshProductChoices() {
            itemsContainer.querySelectorAll('.order-item').forEach(block => {
                if (! block.dataset.productCode) {
                    return;
                }

                block.querySelectorAll('.size-row').forEach(sizeRow => {
                    populateSizeSelect(sizeRow, sizeRow.querySelector('[data-product-id]').value);
                    updateQuantityLimit(sizeRow);
                });

                renderSelectedInfo(block);
            });
            updateAllAddSizeButtons();
            updateComputedTotal();
        }

        function addSizeForBlock(block) {
            const code = block.dataset.productCode;

            if (! code) {
                showBlockInfo(block, 'Vui lòng chọn mã hàng trước khi thêm size.');
                return;
            }

            if (availableProductsForNewSize(block, code).length === 0) {
                showBlockInfo(block, 'Mã hàng này không còn size khác để thêm vào khối này.');
                updateAddSizeButton(block);
                return;
            }

            const sizeRow = addSizeRow(block);
            sizeRow.querySelector('[data-size]').focus();
            updateAddSizeButton(block);
        }

        function addSizeRow(block, size = {}) {
            const sizeRowsContainer = block.querySelector('[data-size-rows]');
            const sizeRow = document.createElement('div');
            sizeRow.className = 'size-row';
            sizeRow.innerHTML = `
                <div class="field">
                    <label>Ảnh</label>
                    <button class="size-thumb" data-size-thumb type="button" disabled aria-label="Phóng to ảnh sản phẩm">
                        <img data-size-thumb-img alt="" style="display:none">
                        <span data-size-thumb-empty>CHƯA CÓ ẢNH</span>
                    </button>
                </div>
                <div class="field">
                    <label>Size</label>
                    <select data-size required disabled>
                        <option value="">Chọn size</option>
                    </select>
                    <input data-product-id type="hidden" value="${size.product_id || ''}" required>
                    <input data-rental type="hidden" value="${size.rental_price != null ? size.rental_price : ''}">
                    <input data-rental-days type="hidden" value="${size.rental_days || 1}">
                    <input data-size-pending type="hidden" value="${size.size_pending ? '1' : '0'}">
                    <input data-fulfillment-source type="hidden" value="stock">
                    <input data-purchase-plan type="hidden" value="">
                </div>
                <div class="field">
                    <label>Số lượng</label>
                    <input data-quantity type="number" min="1" step="1" value="${size.quantity || 1}" required>
                </div>
                <button class="button danger" data-remove-size type="button">Xóa size</button>
                <div class="field size-note-field">
                    <label>Ghi chú (size này)</label>
                    <textarea data-note rows="1" maxlength="500" wrap="soft" aria-label="Ghi chú size này" placeholder="VD: khách dặn giữ nếp, kèm phụ kiện..."></textarea>
                </div>
                <div class="qty-warning" data-qty-warning style="display:none"></div>
            `;

            const sizeSelect = sizeRow.querySelector('[data-size]');
            const productIdInput = sizeRow.querySelector('[data-product-id]');
            const pendingInput = sizeRow.querySelector('[data-size-pending]');
            const quantityInput = sizeRow.querySelector('[data-quantity]');
            const noteInput = sizeRow.querySelector('[data-note]');
            noteInput.value = size.note != null ? size.note : '';
            function resizeNote() {
                noteInput.style.height = 'auto';
                noteInput.style.height = noteInput.scrollHeight + 'px';
            }
            noteInput.addEventListener('input', resizeNote);
            new ResizeObserver(resizeNote).observe(noteInput);

            // Rời ô số lượng: nếu vượt tồn dự kiến thì tự điều chỉnh về tối đa và báo.
            quantityInput.addEventListener('blur', () => {
                if (isPendingRow(sizeRow) || block.querySelector('[data-supply-choice]').value !== 'stock') {
                    return;
                }

                const selected = findProductById(sizeRow.querySelector('[data-product-id]').value);

                if (! selected) {
                    return;
                }

                const limit = remainingStockFor(sizeRow, selected.item);
                const value = Number(quantityInput.value || 0);

                // Hết tồn (các dòng khác cùng mã-size đã dùng hết) thì không hạ về 0 —
                // giữ nguyên số đang nhập và để cảnh báo chặn lưu, tránh gửi số lượng 0.
                if (value > limit && limit >= 1) {
                    quantityInput.value = limit;
                    updateQuantityLimit(sizeRow);
                    setRowQtyWarning(sizeRow, `Đã điều chỉnh về tối đa tồn dự kiến (${limit.toLocaleString('vi-VN')}).`);
                    updateComputedTotal();
                } else if (value > limit) {
                    updateQuantityLimit(sizeRow);
                } else if (value < 1) {
                    quantityInput.value = 1;
                    updateQuantityLimit(sizeRow);
                    updateComputedTotal();
                }
            });

            sizeSelect.addEventListener('change', () => {
                // "Chưa chốt": dùng biến thể đầu tiên của mã làm đại diện (giữ FK/nhóm mã),
                // đánh dấu pending để không giữ kho; số lượng vẫn tính vào tổng.
                if (sizeSelect.value === 'pending') {
                    const group = productOptions.find(item => item.code === block.dataset.productCode);
                    pendingInput.value = '1';
                    productIdInput.value = group ? group.items[0].id : '';
                    clearRowError(block);
                    renderSelectedInfo(block);
                    updateQuantityLimit(sizeRow);
                    refreshProductChoices();
                    return;
                }

                pendingInput.value = '0';

                if (sizeSelect.value && selectedProductIdsInBlock(block, sizeRow).has(String(sizeSelect.value))) {
                    productIdInput.value = '';
                    sizeSelect.value = '';
                    showRowError(block, 'Size này đã có trong khối. Tăng số lượng, hoặc thêm khối mới nếu cần giá khác.');
                    refreshProductChoices();
                    return;
                }

                clearRowError(block);
                productIdInput.value = sizeSelect.value;
                renderSelectedInfo(block);
                updateQuantityLimit(sizeRow);
                refreshProductChoices();
            });

            quantityInput.addEventListener('input', () => {
                updateQuantityLimit(sizeRow);
            });

            sizeRow.querySelector('[data-remove-size]').addEventListener('click', () => {
                if (sizeRowsContainer.querySelectorAll('.size-row').length === 1) {
                    showRowError(block, 'Mỗi mã hàng cần ít nhất một size. Dùng "Xóa mã hàng" để bỏ sản phẩm.');
                    return;
                }

                sizeRow.remove();
                renumberRows();
                refreshProductChoices();
            });

            sizeRowsContainer.appendChild(sizeRow);
            populateSizeSelect(sizeRow, size.product_id || null);
            updateQuantityLimit(sizeRow);
            renumberRows();

            return sizeRow;
        }

        @include('orders.purchase-size-merge')
        function setupPurchasePlan(block, savedPlan) {
            if (typeof savedPlan === 'string') { try { savedPlan = JSON.parse(savedPlan); } catch (_) { savedPlan = null; } }
            let purchaseApplied = savedPlan?.updated_once === true;
            let selectedWorkshopId = savedPlan?.workshop_order_id || null;
            block.appliedPurchaseLines = purchaseApplied ? (savedPlan.applied_lines || []) : [];
            const box = document.createElement('div'); box.className = 'purchase-plan'; box.hidden = true;
            box.innerHTML = `<strong>Size cần mua thêm</strong><div data-purchase-lines></div><button type="button" class="button secondary" data-add-purchase>+ Thêm size cần mua</button><button type="button" class="button secondary" data-apply-purchase>Update vào đơn</button><span data-apply-purchase-state role="status"></span><label>Ngày dự kiến nhận<input data-purchase-date type="date"></label><small>Sửa size, số lượng rồi bấm Update vào đơn. Chỉ điều chỉnh phần chênh lệch so với lần cập nhật trước, không cộng trùng khi bấm lại. Lưu đơn để cập nhật Đặt hàng và dự kiến ở Kho. Hàng chỉ vào tồn thực tế khi bấm Đẩy lên kho hoặc nhập tay tại Kho.</small>`;
            block.querySelector('.product-picker-cell').appendChild(box);
            const lines = box.querySelector('[data-purchase-lines]');
            const date = box.querySelector('[data-purchase-date]'); date.value = savedPlan?.expected_date || '';
            const addWarehouseSize = document.createElement('a'); addWarehouseSize.className = 'button secondary';
            addWarehouseSize.textContent = 'Thêm size trong Kho ↗'; addWarehouseSize.target = '_blank'; addWarehouseSize.rel = 'noopener';
            box.insertBefore(addWarehouseSize, box.querySelector('label'));
            let code = null;
            function savePlan() {
                block.purchasePlan = {workshop_order_id:selectedWorkshopId, expected_date:date.value, updated_once:purchaseApplied, applied_lines:block.appliedPurchaseLines, lines:Array.from(lines.children).map(row => ({
                    product_id:Number(row.querySelector('select').value), quantity:Number(row.querySelector('input').value)
                }))};
                renumberRows();
            }
            function addLine(data = {}) {
                const row = document.createElement('div'); row.className = 'purchase-plan-line';
                const select = document.createElement('select'); select.setAttribute('aria-label', 'Size cần mua'); select.required = true;
                const empty = document.createElement('option'); empty.value = ''; empty.textContent = 'Chọn size cần mua'; select.appendChild(empty);
                const group = productOptions.find(item => item.code === block.dataset.productCode);
                (group?.items || []).forEach(product => { const option = document.createElement('option'); option.value = product.id; option.textContent = product.size; select.appendChild(option); });
                select.value = data.product_id || '';
                const quantity = document.createElement('input'); quantity.type = 'number'; quantity.min = '1'; quantity.step = '1'; quantity.required = true; quantity.value = data.quantity || 1; quantity.setAttribute('aria-label', 'Số lượng cần mua');
                const remove = document.createElement('button'); remove.type = 'button'; remove.textContent = '×'; remove.setAttribute('aria-label', 'Xóa size cần mua');
                remove.addEventListener('click', () => { if (lines.children.length > 1) { row.remove(); savePlan(); } });
                row.append(select, quantity, remove); lines.appendChild(row);
            }
            function refresh() {
                const needed = block.querySelector('[data-supply-choice]').value !== 'stock';
                const group = productOptions.find(item => item.code === block.dataset.productCode);
                addWarehouseSize.hidden = !group;
                if (group) addWarehouseSize.href = @json(route('products.show', ['product' => '__PRODUCT__'])).replace('__PRODUCT__', group.items[0].id);
                box.hidden = !needed;
                if (code !== block.dataset.productCode) {
                    if (code !== null) { savedPlan = null; block.appliedPurchaseLines = []; purchaseApplied = false; selectedWorkshopId = null; }
                    code = block.dataset.productCode;
                    lines.replaceChildren();
                    (savedPlan?.lines?.length ? savedPlan.lines : [{}]).forEach(addLine);
                }
                box.querySelectorAll('input,select,button').forEach(input => input.disabled = !needed);
                date.required = needed; date.max = pickupDateInput.value;
                if (purchaseApplied) box.querySelector('[data-apply-purchase-state]').textContent = 'Đã cập nhật phần mua thêm. Có thể sửa rồi bấm Update; chỉ điều chỉnh phần chênh lệch.';
                savePlan();
            }
            box.addEventListener('input', savePlan); box.addEventListener('change', savePlan);
            box.querySelector('[data-add-purchase]').addEventListener('click', () => { addLine(); savePlan(); });
            block.applyExistingOd = item => {
                const group = productOptions.find(group => group.code === block.dataset.productCode);
                if (!item.lines?.length || item.lines.some(line => !group?.items.some(product => Number(product.id) === Number(line.product_id)))) {
                    showBlockInfo(block, 'Dòng od chưa có size khớp trong Kho. Hãy bổ sung size bên Đặt hàng rồi kiểm tra lại.'); return;
                }
                selectedWorkshopId = item.id;
                lines.replaceChildren(); item.lines.forEach(addLine);
                date.value = item.expected_date || pickupDateInput.value;
                savePlan();
                box.querySelector('[data-apply-purchase]').click();
                block.dispatchEvent(new Event('change', {bubbles:true}));
                box.querySelector('[data-apply-purchase-state]').textContent = 'Đã lấy size và số lượng từ od #' + item.id + '. Bấm Lưu đơn để lưu liên kết, không tạo od mới.';
            };
            box.querySelector('[data-apply-purchase]').addEventListener('click', () => {
                const state = box.querySelector('[data-apply-purchase-state]');
                const group = productOptions.find(item => item.code === block.dataset.productCode);
                const quantities = new Map();
                for (const row of lines.children) {
                    const select = row.querySelector('select');
                    const quantity = row.querySelector('input');
                    if (!select.reportValidity() || !quantity.reportValidity()) return;
                    const id = Number(select.value), amount = Number(quantity.value);
                    if (!group?.items.some(product => Number(product.id) === id) || !Number.isInteger(amount) || amount < 1) {
                        state.textContent = 'Hãy chọn size thuộc mã hàng và số lượng nguyên lớn hơn 0.'; return;
                    }
                    quantities.set(id, (quantities.get(id) || 0) + amount);
                }
                if (!quantities.size) { state.textContent = 'Hãy thêm size cần mua trước.'; return; }
                const existingRows = Array.from(block.querySelectorAll('.size-row')).filter(row => !isPendingRow(row));
                const existing = existingRows.map(row => ({product_id:Number(row.querySelector('[data-product-id]').value), quantity:Number(row.querySelector('[data-quantity]').value)}));
                const result = purchaseSizeUpdates(existing, Array.from(quantities, ([product_id, quantity]) => ({product_id, quantity})), block.appliedPurchaseLines);
                for (const removal of result.removals) {
                    let remaining = removal.quantity;
                    for (const row of existingRows.filter(row => Number(row.querySelector('[data-product-id]').value) === removal.product_id).reverse()) {
                        const input = row.querySelector('[data-quantity]');
                        const removed = Math.min(remaining, Number(input.value));
                        input.value = Number(input.value) - removed;
                        remaining -= removed;
                        if (Number(input.value) === 0) row.remove();
                        if (!remaining) break;
                    }
                }
                for (const addition of result.additions) {
                    let remaining = addition.quantity;
                    let target = existingRows.find(item => Number(item.querySelector('[data-product-id]').value) === addition.product_id);
                    // Resolve the unconfirmed part first instead of counting it twice.
                    // Confirmed sizes and their notes stay in place.
                    const placeholders = Array.from(block.querySelectorAll('.size-row')).filter(row => isPendingRow(row) || !row.querySelector('[data-product-id]').value);
                    for (const placeholder of placeholders) {
                        if (!remaining) break;
                        const input = placeholder.querySelector('[data-quantity]');
                        const quantity = Number(input.value);
                        if (!Number.isInteger(quantity) || quantity < 1) continue;
                        const used = Math.min(remaining, quantity);
                        const note = placeholder.querySelector('[data-note]').value;
                        if (target) {
                            const targetQuantity = target.querySelector('[data-quantity]');
                            targetQuantity.value = Number(targetQuantity.value) + used;
                            const targetNote = target.querySelector('[data-note]');
                            if (note && !targetNote.value.split('\n').includes(note)) targetNote.value = [targetNote.value, note].filter(Boolean).join('\n');
                            if (used === quantity) placeholder.remove(); else input.value = quantity - used;
                        } else if (used === quantity) {
                            placeholder.querySelector('[data-size-pending]').value = '0';
                            placeholder.querySelector('[data-product-id]').value = addition.product_id;
                            populateSizeSelect(placeholder, addition.product_id);
                            target = placeholder; existingRows.push(target);
                        } else {
                            input.value = quantity - used;
                            target = addSizeRow(block, {product_id:addition.product_id, quantity:used, size_pending:false, note});
                            existingRows.push(target);
                        }
                        remaining -= used;
                    }
                    if (remaining && target) {
                        const input = target.querySelector('[data-quantity]'); input.value = Number(input.value) + remaining;
                    } else if (remaining) {
                        target = addSizeRow(block, {product_id:addition.product_id, quantity:remaining, size_pending:false});
                        existingRows.push(target);
                    }
                }
                block.appliedPurchaseLines = result.applied;
                purchaseApplied = true;
                savePlan(); renumberRows(); refreshProductChoices();
                state.textContent = result.additions.length || result.removals.length
                    ? 'Đã điều chỉnh phần mua thêm vào đơn. Bấm Lưu đơn để lưu.'
                    : 'Số lượng không thay đổi, không cộng trùng.';
            });
            block.querySelector('[data-supply-choice]').addEventListener('change', () => { refresh(); refreshProductChoices(); });
            block.addEventListener('product-updated', refresh);
            pickupDateInput.addEventListener('change', () => { date.max = pickupDateInput.value; });
        }

        function addItemBlock(blockData = {}) {
            const block = document.createElement('tr');
            block.className = 'order-item';
            block.dataset.index = itemIndex;
            const searchId = `product_search_${itemIndex}`;
            block.innerHTML = `
                <td class="entry-number"><span data-block-number></span><div class="drag-handle" data-drag-handle title="Kéo để sắp xếp">&#8942;&#8942;</div></td>
                <td class="product-picker-cell"><label class="entry-sr-only" for="${searchId}">Mã hàng</label><select data-category aria-label="Lọc danh mục"><option value="">Tất cả danh mục</option></select><input id="${searchId}" data-search type="search" autocomplete="off" placeholder="Nhập mã hoặc tên…" required><button class="entry-code-thumb" data-size-thumb type="button" disabled aria-label="Phóng to ảnh sản phẩm"><img data-code-preview alt="" hidden></button><div data-suggestions class="product-suggestions"></div><div data-row-error class="error row-error"></div></td>
                <td><div data-size-rows class="size-rows"></div><div style="margin:12px 0;padding:10px 12px;background:#eff6ff;border:1px solid #c3d4ed;border-radius:8px;color:#1e3a5f;font-weight:800" role="status">Tổng số lượng: <span data-block-quantity>0</span></div><button class="button secondary" data-add-size type="button" disabled>+ Thêm size</button></td>
                <td><label class="entry-sr-only">Giá thuê</label><input data-block-rental type="number" min="0" step="1000" value="0" aria-label="Giá thuê mã hàng"></td>
                <td><input data-block-days type="text" inputmode="decimal" value="1" required aria-label="Số ngày thuê mã hàng" placeholder="1,5"></td>
                <td class="entry-amount"><span data-block-subtotal>0</span></td>
                <td><button class="button danger" data-remove-block type="button" aria-label="Xóa mã hàng" title="Xóa mã hàng">×</button></td>
            `;
            itemIndex += 1;

            const sourceBox = document.createElement('div'); sourceBox.className = 'entry-supply-choice';
            const productInfo = document.createElement('div'); productInfo.dataset.selectedInfo = ''; productInfo.className = 'selected-product-info';
            block.querySelector('.product-picker-cell').appendChild(productInfo);
            sourceBox.innerHTML = `<label>Nguồn hàng mã này</label><select data-supply-choice aria-label="Nguồn hàng mã này"><option value="stock">Sẵn kho</option><option value="xuong_may">Cần od – Xưởng</option><option value="mua_ngoai">Cần od – Mua ngoài</option></select><a data-od-link target="_blank" rel="noopener" hidden>Đến đặt hàng od ↗</a><small data-od-hint hidden>Lưu đơn trước để mở đúng mã và số lượng cần đặt.</small>`;
            block.querySelector('.product-picker-cell').appendChild(sourceBox);
            const supplyChoice = block.querySelector('[data-supply-choice]');
            const checkOd = document.createElement('button'); checkOd.type = 'button'; checkOd.className = 'button secondary';
            checkOd.textContent = 'Kiểm tra đã od';
            const relatedOd = document.createElement('div'); relatedOd.setAttribute('aria-live', 'polite');
            sourceBox.append(checkOd, relatedOd);
            let relatedKey = '', relatedRequest = 0;
            async function loadRelatedOd(force = false) {
                const code = block.dataset.productCode;
                const source = supplyChoice.value;
                const needed = source !== 'stock' && !!code;
                checkOd.hidden = !needed; relatedOd.hidden = !needed;
                const key = needed ? code + '|' + source : '';
                if (!force && key === relatedKey) return;
                relatedKey = key;
                const requestId = ++relatedRequest;
                relatedOd.replaceChildren();
                if (!needed) return;
                relatedOd.textContent = 'Đang kiểm tra các dòng đặt hàng…';
                try {
                    const url = new URL(@json(route('workshop-orders.related-orders')));
                    url.searchParams.set('code', code); url.searchParams.set('supply_source', source);
                    const response = await fetch(url, {credentials:'same-origin', headers:{Accept:'application/json'}});
                    if (!response.ok) throw new Error('Chưa kiểm tra được. Bấm Kiểm tra đã od để thử lại.');
                    const rows = await response.json();
                    if (requestId !== relatedRequest || !block.isConnected) return;
                    relatedOd.replaceChildren();
                    const title = document.createElement('p');
                    title.textContent = rows.length ? 'Các dòng đặt hàng cùng mã và nguồn (mới nhất):' : 'Chưa tìm thấy dòng đặt hàng cùng mã và nguồn.';
                    relatedOd.appendChild(title);
                    rows.forEach(item => {
                        const details = document.createElement('details');
                        const summary = document.createElement('summary');
                        summary.textContent = '#' + item.id + ' · ' + (item.order_name || 'Chưa có tên đơn') + ' · ' + item.quantity + ' · ' + item.status;
                        const info = document.createElement('p');
                        const sameOrder = item.customer_order_id && Number(item.customer_order_id) === Number(currentOrderId);
                        info.textContent = (sameOrder ? 'Thuộc đơn khách này. ' : 'Đối chiếu với đơn bạn đang lên. ')
                            + 'Size: ' + (item.size_note || 'Chưa ghi') + ' · Nhà cung cấp: ' + (item.supplier || 'Chưa ghi')
                            + ' · Ngày đặt: ' + (item.ordered_date || 'Chưa ghi') + (item.stocked ? ' · Đã nhập kho' : '');
                        const link = document.createElement('a'); link.href = item.url; link.target = '_blank'; link.rel = 'noopener';
                        link.textContent = 'Xem dòng đặt hàng ↗';
                        const apply = document.createElement('button'); apply.type = 'button'; apply.className = 'button secondary';
                        const otherOrder = item.customer_order_id && Number(item.customer_order_id) !== Number(currentOrderId);
                        apply.textContent = otherOrder ? 'Đã thuộc đơn khách khác' : 'Chọn od này — Update vào đơn';
                        apply.disabled = !!otherOrder;
                        apply.addEventListener('click', () => block.applyExistingOd(item));
                        details.append(summary, info, link); relatedOd.append(details, apply);
                    });
                } catch (error) { if (requestId === relatedRequest) relatedOd.textContent = error.message; }
            }
            checkOd.addEventListener('click', () => loadRelatedOd(true));
            supplyChoice.value = blockData.fulfillment_source || 'stock';
            setupPurchasePlan(block, blockData.purchase_plan);
            let odDirty = false;
            function updateOdLink() {
                loadRelatedOd();
                const needed = supplyChoice.value !== 'stock';
                const link = block.querySelector('[data-od-link]');
                const savedOrderId = currentOrderId;
                const canOpen = needed && savedOrderId && block.dataset.productCode && !odDirty
                    && block.dataset.productCode === blockData.code && supplyChoice.value === (blockData.fulfillment_source || 'stock');
                link.hidden = !canOpen;
                link.style.display = canOpen ? '' : 'none';
                const hint = block.querySelector('[data-od-hint]');
                hint.hidden = !needed || !!canOpen;
                hint.style.display = hint.hidden ? 'none' : 'block';
                sourceBox.classList.toggle('needs-od', needed);
                const url = new URL(@json(route('workshop-orders.create')));
                url.searchParams.set('customer_order_id', savedOrderId || '');
                url.searchParams.set('product_code', block.dataset.productCode || '');
                url.searchParams.set('supply_source', supplyChoice.value);
                if (canOpen) link.href = url.toString(); else link.removeAttribute('href');
            }
            block.addEventListener('input', () => { odDirty = true; updateOdLink(); });
            block.addEventListener('change', () => { odDirty = true; updateOdLink(); });
            block.addEventListener('order-autosaved', () => {
                odDirty = false; blockData.code = block.dataset.productCode;
                blockData.fulfillment_source = supplyChoice.value; updateOdLink(); loadRelatedOd(true);
            });
            block.querySelector('[data-od-link]').addEventListener('click', event => {
                if (!event.currentTarget.hasAttribute('href')) {
                    event.preventDefault(); showBlockInfo(block, 'Hãy lưu đơn khách trước khi mở đặt hàng od.');
                }
            });
            supplyChoice.addEventListener('change', () => { renumberRows(); updateOdLink(); });
            block.addEventListener('input', updateOdLink);
            block.addEventListener('change', updateOdLink);
            block.addEventListener('product-updated', updateOdLink);

            const searchInput = block.querySelector('[data-search]');
            const selectedInfo = block.querySelector('[data-selected-info]');
            const categorySelect = block.querySelector('[data-category]');
            categorySelect.before(searchInput);
            categorySelect.options[0].textContent = 'Lọc danh mục (không bắt buộc)';
            [...new Set(productOptions.flatMap(group => group.items.map(item => item.category)).filter(Boolean))].sort((a,b) => a.localeCompare(b,'vi')).forEach(category => { const option = document.createElement('option'); option.value = category; option.textContent = category; categorySelect.appendChild(option); });
            categorySelect.addEventListener('change', () => renderSuggestions(block, ''));

            block.querySelector('[data-block-rental]').addEventListener('input', updateComputedTotal);
            block.querySelector('[data-block-days]').addEventListener('input', updateComputedTotal);
            block.querySelector('[data-block-days]').value = blockData.days || 1;

            searchInput.addEventListener('input', () => {
                categorySelect.value = '';
                if (block.dataset.productCode) {
                    block.dataset.productCode = '';
                    clearSizeRows(block);
                    addSizeRow(block);
                }

                renderSelectedInfo(block);
                clearRowError(block);
                updateAddSizeButton(block);
                if (searchInput.value.trim()) renderSuggestions(block, searchInput.value);
                else block.querySelector('[data-suggestions]').style.display = 'none';
            });

            searchInput.addEventListener('focus', () => {
                block.querySelector('[data-suggestions]').style.display = 'none';
            });
            searchInput.addEventListener('keydown', event => {
                if (event.key !== 'Enter') return;
                event.preventDefault();
                const code = searchInput.value.trim().toLowerCase();
                const group = productOptions.find(item => item.code.toLowerCase() === code);
                if (group) {
                    categorySelect.value = '';
                    selectProductCode(block, group.code);
                } else if (code) {
                    renderSuggestions(block, searchInput.value);
                }
            });

            block.querySelector('[data-add-size]').addEventListener('click', () => addSizeForBlock(block));

            block.querySelector('[data-remove-block]').addEventListener('click', () => {
                if (itemsContainer.querySelectorAll('.order-item').length === 1) {
                    showBlockInfo(block, 'Đơn hàng cần ít nhất một sản phẩm.');
                    return;
                }

                block.remove();
                renumberRows();
                refreshProductChoices();
            });

            itemsContainer.appendChild(block);

            if (blockData.code) {
                const group = productOptions.find(item => item.code === blockData.code);
                searchInput.value = group ? `${group.code} - ${group.name}` : blockData.code;
                block.dataset.productCode = group ? group.code : '';
            }

            const sizes = (blockData.sizes && blockData.sizes.length) ? blockData.sizes : [{}];
            sizes.forEach(size => addSizeRow(block, size));

            // Giá thuê mặc định: dùng giá đã lưu trên đơn nếu có, nếu không lấy giá thuê ở kho.
            // Nhận cả giá 0 (hàng tặng kèm) — trước đây coi 0 là "chưa có giá" nên mở
            // form sửa là dòng đó tự nhảy về giá kho, lưu lại thành tính tiền oan.
            const rentalInput = block.querySelector('[data-block-rental]');
            const savedRental = blockData.rental;
            const hasSavedRental = savedRental !== null && savedRental !== undefined
                && savedRental !== '' && ! isNaN(Number(savedRental));

            if (hasSavedRental) {
                rentalInput.value = Number(savedRental);
            } else if (block.dataset.productCode) {
                rentalInput.value = codeRentalPrice(block.dataset.productCode);
            }

            renderSelectedInfo(block);
            updateAddSizeButton(block);
            renumberRows();
            updateComputedTotal();

            updateOdLink();
            block.dispatchEvent(new Event('product-updated'));
            const pickerCell = block.querySelector('.product-picker-cell');
            const codeLayout = document.createElement('div'); codeLayout.className = 'order-code-layout';
            const codeMain = document.createElement('div'); codeMain.className = 'order-code-main';
            const purchasePanel = pickerCell.querySelector('.purchase-plan');
            Array.from(pickerCell.childNodes).filter(node => node !== purchasePanel).forEach(node => codeMain.appendChild(node));
            codeLayout.append(codeMain, purchasePanel); pickerCell.appendChild(codeLayout);
            return block;
        }

        document.addEventListener('click', event => {
            itemsContainer.querySelectorAll('[data-suggestions]').forEach(suggestions => {
                const block = suggestions.closest('.order-item');

                if (!block.contains(event.target)) {
                    suggestions.style.display = 'none';
                }
            });
        });

        window.addEventListener('scroll', event => {
            if (event.target instanceof Element && event.target.closest('.product-suggestions')) return;
            itemsContainer.querySelectorAll('[data-suggestions]').forEach(box => box.style.display = 'none');
        }, true);
        window.addEventListener('resize', () => itemsContainer.querySelectorAll('[data-suggestions]').forEach(box => box.style.display = 'none'));
        const lightbox = document.querySelector('[data-image-lightbox]');
        const lightboxImage = document.querySelector('[data-lightbox-image]');

        function closeLightbox() {
            lightbox.classList.remove('is-open');
            lightbox.setAttribute('aria-hidden', 'true');
            lightboxImage.src = '';
        }

        itemsContainer.addEventListener('click', event => {
            const thumb = event.target.closest('[data-size-thumb]');

            if (! thumb || ! thumb.dataset.fullImage) {
                return;
            }

            lightboxImage.src = thumb.dataset.fullImage;
            lightboxImage.alt = 'Ảnh sản phẩm';
            lightbox.classList.add('is-open');
            lightbox.setAttribute('aria-hidden', 'false');
        });

        lightbox.addEventListener('click', closeLightbox);

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && lightbox.classList.contains('is-open')) {
                closeLightbox();
            }
        });

        // ===== Keo tha de doi vi tri the ma hang =====
        // Chi de nhin cho de; khong doi gia, so luong hay ton kho cua bat ky dong nao.
        // Thu tu moi duoc luu theo don vi renumberRows() danh lai chi so theo DOM.
        let draggingBlock = null;

        function clearDropMarks() {
            itemsContainer.querySelectorAll('.order-item').forEach(b => b.classList.remove('is-drop-target'));
        }

        // HTML5 drag yeu cau draggable tren chinh khoi, nhung neu bat san thi
        // khong con boi den trong o nhap duoc. Vi vay chi bat khi cam vao tay keo.
        itemsContainer.addEventListener('mousedown', event => {
            const handle = event.target.closest('[data-drag-handle]');
            const block = event.target.closest('.order-item');

            if (handle && block) {
                block.draggable = true;
            }
        });

        itemsContainer.addEventListener('mouseup', () => {
            itemsContainer.querySelectorAll('.order-item').forEach(b => { b.draggable = false; });
        });

        itemsContainer.addEventListener('dragstart', event => {
            const block = event.target.closest('.order-item');
            if (! block || ! block.draggable) return;

            draggingBlock = block;
            block.classList.add('is-dragging');
            event.dataTransfer.effectAllowed = 'move';
            // Firefox chi khoi dong keo khi co du lieu duoc set.
            event.dataTransfer.setData('text/plain', '');
        });

        itemsContainer.addEventListener('dragover', event => {
            if (! draggingBlock) return;

            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';

            const target = event.target.closest('.order-item');
            if (! target || target === draggingBlock) return;

            clearDropMarks();
            target.classList.add('is-drop-target');
        });

        itemsContainer.addEventListener('drop', event => {
            if (! draggingBlock) return;

            event.preventDefault();

            const target = event.target.closest('.order-item');

            if (target && target !== draggingBlock) {
                const blocks = Array.from(itemsContainer.querySelectorAll('.order-item'));
                const keoTruoc = blocks.indexOf(draggingBlock) < blocks.indexOf(target);

                target.parentNode.insertBefore(draggingBlock, keoTruoc ? target.nextSibling : target);
                // Chi so items[i] bam theo thu tu DOM nen phai danh lai sau khi doi cho.
                renumberRows();
            }

            clearDropMarks();
        });

        itemsContainer.addEventListener('dragend', () => {
            if (draggingBlock) {
                draggingBlock.classList.remove('is-dragging');
                draggingBlock.draggable = false;
                draggingBlock = null;
            }

            clearDropMarks();
        });

        addItemButton.addEventListener('click', () => addItemBlock());

        [pickupDateInput, eventDateInput, returnDateInput, document.getElementById('return_period')].forEach(input => {
            input.addEventListener('change', () => refreshAvailability('date-change'));
        });

        orderForm.addEventListener('submit', event => {
            updateComputedTotal();
            const sizeRows = Array.from(itemsContainer.querySelectorAll('.size-row'));

            itemsContainer.querySelectorAll('.order-item').forEach(clearRowError);

            const invalidRow = sizeRows.find(sizeRow => ! sizeRow.querySelector('[data-product-id]').value);

            if (invalidRow) {
                event.preventDefault();
                const block = invalidRow.closest('.order-item');
                showBlockInfo(block, 'Vui lòng chọn mã hàng và size trước khi lưu đơn.');
                invalidRow.querySelector('[data-size]').focus();
                return;
            }

            // Chỉ chặn trùng size TRONG CÙNG MỘT KHỐI (khác khối cùng mã được phép).
            let duplicateRow = null;

            itemsContainer.querySelectorAll('.order-item').forEach(block => {
                if (duplicateRow) {
                    return;
                }

                const seen = new Set();

                block.querySelectorAll('.size-row').forEach(sizeRow => {
                    if (duplicateRow || isPendingRow(sizeRow)) {
                        return;
                    }

                    const productId = sizeRow.querySelector('[data-product-id]').value;

                    if (! productId) {
                        return;
                    }

                    if (seen.has(productId)) {
                        duplicateRow = sizeRow;
                        return;
                    }

                    seen.add(productId);
                });
            });

            if (duplicateRow) {
                event.preventDefault();
                const block = duplicateRow.closest('.order-item');
                showRowError(block, 'Size này bị lặp trong cùng khối. Tăng số lượng, hoặc tách sang khối mới nếu cần giá khác.');
                duplicateRow.querySelector('[data-size]').focus();
                return;
            }

            const invalidQuantityRow = sizeRows.find(sizeRow => ! validateSizeRow(sizeRow));

            if (invalidQuantityRow) {
                event.preventDefault();
                const block = invalidQuantityRow.closest('.order-item');
                const max = invalidQuantityRow.querySelector('[data-quantity]').max;
                showRowError(block, `Số lượng không được vượt quá tồn dự kiến (${max}).`);
                invalidQuantityRow.querySelector('[data-quantity]').focus();
            }
        });

        // ===== Tự lưu nháp vào trình duyệt (cả khi TẠO MỚI và khi SỬA đơn) =====
        // Khi sửa, mỗi đơn có một khoá riêng để nháp của đơn này không đè lên đơn khác.
        const EDIT_DRAFT_PREFIX = 'tibastore_order_edit_draft_v1_';
        const isCreate = @json($mode === 'create');
        let DRAFT_KEY = @json(
            $mode === 'create'
                ? 'tibastore_order_create_draft_v1'
                : 'tibastore_order_edit_draft_v1_' . $order->id
        );
        // Thời điểm đơn được lưu lần gần nhất trên server (ms). Nháp cũ hơn mốc này
        // là nháp lỗi thời (đơn đã được lưu ở nơi khác) nên sẽ bị bỏ, không khôi phục.
        const orderSavedAt = @json($mode === 'create' ? 0 : (($order->updated_at?->getTimestamp() ?? 0) * 1000));
        const hasOldInput = @json(! empty(old()));

        function readFormDraft() {
            const items = [];
            itemsContainer.querySelectorAll('.order-item').forEach(block => {
                const rentalEl = block.querySelector('[data-block-rental]');
                const rental = rentalEl ? rentalEl.value : '';
                block.querySelectorAll('.size-row').forEach(row => {
                    const noteEl = row.querySelector('[data-note]');
                    items.push({
                        product_id: row.querySelector('[data-product-id]').value,
                        quantity: row.querySelector('[data-quantity]').value,
                        rental_price: rental,
                        rental_days: block.querySelector('[data-block-days]').value,
                        size_pending: isPendingRow(row) ? 1 : 0,
                        fulfillment_source: block.querySelector('[data-supply-choice]').value,
                        purchase_plan: block.purchasePlan || null,
                        note: noteEl ? noteEl.value : '',
                    });
                });
            });
            const val = id => { const el = document.getElementById(id); return el ? el.value : ''; };
            return {
                autosave_token: orderForm.querySelector('[name=_autosave_token]').value,
                closer_name: val('closer_name'), order_name: val('order_name'),
                note: val('note'),
                phone: val('phone'), address: val('address'),
                region: val('region'), carrier: val('carrier'),
                pickup_date: val('pickup_date'), event_date: val('event_date'), return_date: val('return_date'),
                return_period: val('return_period'),
                status: val('status'), source: val('source'),
                shipping_fee: val('shipping_fee'), payment_1: val('payment_1'), payment_2: val('payment_2'),
                discount_amount: val('discount_amount'),
                discount_type: val('discount_type'),
                items, savedAt: Date.now(),
            };
        }

        let draftSaveTimer = null;
        function saveDraft() {
            clearTimeout(draftSaveTimer);
            draftSaveTimer = setTimeout(saveDraftNow, 400);
        }
        function saveDraftNow() {
            try { localStorage.setItem(DRAFT_KEY, JSON.stringify(readFormDraft())); } catch (e) {}
        }
        function clearDraft() {
            try { localStorage.removeItem(DRAFT_KEY); } catch (e) {}
        }

        // Dọn nháp sửa đơn bỏ quên quá 7 ngày để localStorage không phình mãi.
        function pruneOldEditDrafts() {
            const limit = Date.now() - 7 * 24 * 60 * 60 * 1000;
            try {
                Object.keys(localStorage)
                    .filter(key => key.startsWith(EDIT_DRAFT_PREFIX) && key !== DRAFT_KEY)
                    .forEach(key => {
                        let saved = 0;
                        try { saved = (JSON.parse(localStorage.getItem(key) || '{}').savedAt) || 0; } catch (e) {}
                        if (saved < limit) localStorage.removeItem(key);
                    });
            } catch (e) {}
        }
        pruneOldEditDrafts();

        function restoreTopFields(d) {
            if (d.autosave_token) orderForm.querySelector('[name=_autosave_token]').value = d.autosave_token;
            const set = (id, v) => { const el = document.getElementById(id); if (el && v != null) el.value = v; };
            set('closer_name', d.closer_name); set('order_name', d.order_name);
            set('note', d.note);
            set('phone', d.phone); set('address', d.address);
            set('region', d.region); set('carrier', d.carrier);
            set('pickup_date', d.pickup_date); set('event_date', d.event_date); set('return_date', d.return_date);
            set('return_period', d.return_period || 'sang');
            set('status', d.status); set('source', d.source);
            set('shipping_fee', d.shipping_fee); set('payment_1', d.payment_1); set('payment_2', d.payment_2);
            set('discount_amount', d.discount_amount);
            set('discount_type', d.discount_type || 'vnd');
        }

        // Dữ liệu để dựng: ưu tiên old() (khi submit lỗi), rồi tới nháp đã lưu, rồi mặc định.
        let itemsToBuild = initialItems;
        let restoredFromDraft = false;

        if (! hasOldInput) {
            let draft = null;
            try { draft = JSON.parse(localStorage.getItem(DRAFT_KEY) || 'null'); } catch (e) {}

            // Khi SỬA đơn: chỉ khôi phục nháp mới hơn lần lưu gần nhất của đơn.
            // Nếu đơn đã được lưu sau khi nháp được tạo, dữ liệu trên server mới hơn
            // nên phải tôn trọng nó thay vì đè bằng nháp cũ.
            if (draft && ! isCreate && ! ((draft.savedAt || 0) > orderSavedAt)) {
                clearDraft();
                draft = null;
            }

            if (draft && Array.isArray(draft.items)) {
                restoreTopFields(draft);
                if (draft.items.length > 0) {
                    itemsToBuild = draft.items;
                }
                restoredFromDraft = true;
            }
        }

        const groupedBlocks = [];
        const codeToBlockIndex = new Map();

        itemsToBuild.forEach(item => {
            const selected = findProductById(item.product_id);
            const code = selected ? selected.group.code : null;
            const sizeData = { product_id: item.product_id, quantity: item.quantity, rental_price: item.rental_price, rental_days: item.rental_days || 1, size_pending: item.size_pending, note: item.note };

            // Gộp theo MÃ + GIÁ THUÊ: cùng mã nhưng khác giá sẽ thành 2 khối riêng.
            const groupKey = code !== null ? code + '|' + (item.rental_price != null ? item.rental_price : '') + '|' + (item.rental_days || 1) + '|' + (item.fulfillment_source || 'stock') : null;

            if (groupKey !== null && codeToBlockIndex.has(groupKey)) {
                groupedBlocks[codeToBlockIndex.get(groupKey)].sizes.push(sizeData);
                return;
            }

            if (groupKey !== null) {
                codeToBlockIndex.set(groupKey, groupedBlocks.length);
            }

            // Giá thuê của mã lấy từ dòng đầu tiên (các size cùng mã dùng chung giá).
            groupedBlocks.push({ code, sizes: [sizeData], rental: item.rental_price, days: item.rental_days || 1, fulfillment_source: item.fulfillment_source || 'stock', purchase_plan: item.purchase_plan || null });
        });

        if (groupedBlocks.length > 0) {
            groupedBlocks.forEach(blockData => addItemBlock(blockData));
        } else {
            addItemBlock();
        }

        itemsContainer.addEventListener('input', event => {
            if (event.target.matches('[data-quantity]')) {
                // Đổi số lượng một dòng làm thay đổi tồn còn lại của các dòng khác
                // cùng mã-size (ở khối giá khác), nên kiểm lại toàn bộ.
                itemsContainer.querySelectorAll('.size-row').forEach(updateQuantityLimit);
                updateComputedTotal();
            }
        });

        resetAvailabilityToCurrentStock();
        refreshAvailability();
        updateComputedTotal();

        // ===== Kích hoạt tự lưu / khôi phục / xoá nháp =====
        orderForm.addEventListener('input', saveDraft);
        orderForm.addEventListener('change', saveDraft);
        new MutationObserver(saveDraft).observe(itemsContainer, { childList: true, subtree: true });

        // Trang tải lại sau lỗi (có old): lưu ngay để nếu reload thủ công vẫn khôi phục được.
        if (hasOldInput) {
            saveDraftNow();
        }

        if (restoredFromDraft) {
            const notice = document.getElementById('draft_notice');
            if (notice) notice.style.display = 'flex';
        }

        const clearBtn = document.getElementById('draft_clear');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                clearDraft();
                // Tạo mới: về form trống. Sửa đơn: nạp lại dữ liệu gốc từ server.
                window.location = window.location.pathname;
            });
        }

        // Đơn thực sự được gửi (không bị chặn validate) → xoá nháp.
        // Thành công sẽ chuyển trang; nếu lỗi validate thì old() sẽ dựng lại + lưu nháp mới.
        @include('orders.form-history')
        @include('orders.autosave')
    </script>
    </div>
</div>
@include('orders.list-position')
@include('orders.customer-suggestions')
</body>
</html>
