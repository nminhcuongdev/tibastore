<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mode === 'create' ? 'Tạo đơn hàng' : 'Sửa đơn hàng' }}</title>
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
        .content { padding: 36px clamp(18px, 5vw, 56px) 56px; }
        .heading { margin-bottom: 22px; }
        .heading h1 {
            color: #6f253f;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(32px, 4vw, 48px);
            line-height: 1.08;
            margin: 0 0 8px;
        }
        .heading p {
            color: #704252;
            margin: 0;
        }
        .form-shell {
            background: #fff;
            border: 1px solid #f0d3dc;
            border-radius: 8px;
            box-shadow: 0 18px 45px rgba(117, 44, 69, .08);
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
            background: #fffafb;
            border: 1px solid #f2d3dc;
            border-radius: 8px;
            display: grid;
            gap: 12px;
            min-width: 0;
            padding: 14px;
            position: relative;
        }
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
            background: #f9e5ec;
            border: 1px solid #f1cbd7;
            border-radius: 8px;
            color: #a64465;
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
            border-color: #c9577d;
            box-shadow: 0 10px 24px rgba(117, 44, 69, .18);
            outline: none;
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
        .block-subtotal {
            color: #7a344c;
            font-weight: 800;
            padding-bottom: 12px;
        }
        .block-subtotal span {
            color: #a13b60;
        }
        .product-picker-cell {
            min-width: 0;
            position: relative;
        }
        .product-suggestions {
            background: #fff;
            border: 1px solid #f0d3dc;
            border-radius: 8px;
            box-shadow: 0 14px 36px rgba(117, 44, 69, .08);
            display: none;
            left: 0;
            margin-top: 6px;
            max-height: 220px;
            overflow-y: auto;
            position: absolute;
            right: 0;
            top: 100%;
            z-index: 5;
        }
        .product-suggestion {
            background: transparent;
            border: 0;
            border-bottom: 1px solid #f7e3e9;
            color: #3f2730;
            cursor: pointer;
            display: block;
            padding: 10px 12px;
            text-align: left;
            width: 100%;
        }
        .product-suggestion:hover { background: #fff4f7; }
        .product-code {
            color: #a13b60;
            font-weight: 900;
        }
        .product-name,
        .product-meta {
            color: #8b6672;
            font-size: 13px;
        }
        .selected-product-info {
            background: #fff4f7;
            border: 1px solid #f2d3dc;
            border-radius: 8px;
            color: #704252;
            display: none;
            font-size: 13px;
            grid-column: 1 / -1;
            line-height: 1.45;
            padding: 10px 12px;
        }
        label {
            color: #7a344c;
            font-size: 13px;
            font-weight: 800;
        }
        input,
        select {
            background: #fff;
            border: 1px solid #ebc5d2;
            border-radius: 8px;
            color: #3f2730;
            font-size: 15px;
            min-height: 44px;
            padding: 10px 13px;
            width: 100%;
        }
        .readonly-value {
            align-items: center;
            background: #fff4f7;
            border: 1px solid #f2d3dc;
            border-radius: 8px;
            color: #7a344c;
            display: flex;
            font-weight: 900;
            min-height: 44px;
            padding: 10px 13px;
        }
        input:focus,
        select:focus {
            border-color: #c9577d;
            box-shadow: 0 0 0 3px rgba(201, 87, 125, .16);
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
            border: 1px solid #f0c7d3;
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
    </style>
</head>
<body>
    <header class="topbar">
        <a class="brand" href="{{ route('orders.index') }}">Tiba Boutique</a>
        <a class="button secondary" href="{{ route('orders.index') }}">Về danh sách</a>
    </header>

    <main class="content">
        <div class="heading">
            <h1>{{ $mode === 'create' ? 'Tạo đơn hàng mới' : 'Sửa thông tin đơn hàng' }}</h1>
            <p>Chọn nhiều sản phẩm từ kho, cập nhật lịch lấy, lịch diễn, lịch trả và trạng thái xử lý đơn.</p>
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
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $oldItems = old('items');
            $initialItems = $oldItems !== null
                ? collect($oldItems)->map(fn ($item) => [
                    'product_id' => $item['product_id'] ?? '',
                    'quantity' => $item['quantity'] ?? 1,
                    'rental_price' => $item['rental_price'] ?? null,
                    'size_pending' => ! empty($item['size_pending']),
                    'note' => $item['note'] ?? null,
                ])->values()
                : ($orderItems->isNotEmpty()
                    ? $orderItems->map(fn ($item) => [
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'rental_price' => $item->rental_price,
                        'size_pending' => (bool) $item->size_pending,
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
            @csrf
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
                <label for="address">Địa chỉ</label>
                <input id="address" name="address" type="text" value="{{ old('address', $order->address) }}" placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành...">
                @error('address') <div class="error">{{ $message }}</div> @enderror
            </div>

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
            </div>

            <div class="field">
                <label for="status">Trạng thái</label>
                <select id="status" name="status" required>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $order->status ?? \App\Models\Order::DEFAULT_STATUS) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status') <div class="error">{{ $message }}</div> @enderror
            </div>

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
                <label>Tổng đơn (tự động: giá thuê × số lượng)</label>
                <div class="readonly-value" data-total-display>0</div>
            </div>

            <div class="field">
                <label for="shipping_fee">Tiền ship</label>
                <input id="shipping_fee" name="shipping_fee" type="number" min="0" step="1" value="{{ old('shipping_fee', $order->shipping_fee ?? 0) }}">
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

            <div class="field full">
                <label>Sản phẩm trong đơn</label>
                <div id="stock_conflict" class="stock-conflict" style="display:none"></div>
                <div id="order_items" class="order-items"></div>
                <div class="items-actions">
                    <button id="add_item_button" class="button secondary" type="button">+ Thêm sản phẩm</button>
                </div>
                @error('items') <div class="error">{{ $message }}</div> @enderror
                @error('items.*.product_id') <div class="error">{{ $message }}</div> @enderror
                @error('items.*.quantity') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="actions">
                <a class="button secondary" href="{{ route('orders.index') }}">Hủy</a>
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
        const currentOrderId = @json($mode === 'edit' ? $order->id : null);
        const itemsContainer = document.getElementById('order_items');
        const addItemButton = document.getElementById('add_item_button');
        const orderForm = document.getElementById('order_form');
        const pickupDateInput = document.getElementById('pickup_date');
        const eventDateInput = document.getElementById('event_date');
        const returnDateInput = document.getElementById('return_date');
        const csrfToken = orderForm.querySelector('input[name="_token"]').value;
        const productAvailability = new Map();
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

        // Giá thuê nhập tại mã hàng áp cho mọi size của mã đó.
        // Thành tiền mỗi mã = giá thuê × tổng số lượng các size; tổng đơn = cộng dồn các mã.
        function updateComputedTotal() {
            let total = 0;

            itemsContainer.querySelectorAll('.order-item').forEach(block => {
                const rentalInput = block.querySelector('[data-block-rental]');
                const rental = Number(rentalInput ? rentalInput.value || 0 : 0);
                let blockQuantity = 0;

                block.querySelectorAll('.size-row').forEach(sizeRow => {
                    const productId = sizeRow.querySelector('[data-product-id]').value;
                    const quantity = Number(sizeRow.querySelector('[data-quantity]').value || 0);
                    const rentalHidden = sizeRow.querySelector('[data-rental]');

                    // Ghi giá thuê của mã vào input ẩn từng size để gửi lên server.
                    if (rentalHidden) {
                        rentalHidden.value = String(rental);
                    }

                    if (productId && quantity > 0) {
                        blockQuantity += quantity;
                    }
                });

                const subtotal = rental * blockQuantity;
                const subtotalEl = block.querySelector('[data-block-subtotal]');

                if (subtotalEl) {
                    subtotalEl.textContent = subtotal.toLocaleString('vi-VN');
                }

                total += subtotal;
            });

            if (totalDisplay) {
                totalDisplay.textContent = total.toLocaleString('vi-VN');
            }
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
                Object.entries(data.availability || {}).forEach(([productId, quantity]) => {
                    productAvailability.set(Number(productId), Number(quantity || 0));
                });
                refreshProductChoices();
                itemsContainer.querySelectorAll('.size-row').forEach(validateSizeRow);

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
                        const limit = stockLimit(selected.item);
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

                return isCurrentSelection || (! selectedIds.has(String(product.id)) && stockLimit(product) > 0);
            });
        }

        function availableProductsForNewSize(block, code) {
            const group = productOptions.find(item => item.code === code);

            if (! group) {
                return [];
            }

            const selectedIds = block ? selectedProductIdsInBlock(block) : new Set();

            return group.items.filter(product => ! selectedIds.has(String(product.id)) && stockLimit(product) > 0);
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

        function updateQuantityLimit(sizeRow) {
            const quantityInput = sizeRow.querySelector('[data-quantity]');
            const productId = sizeRow.querySelector('[data-product-id]').value;
            const selected = findProductById(productId);

            quantityInput.setCustomValidity('');
            quantityInput.removeAttribute('max');

            // Dòng "chưa chốt" không giữ kho theo size nên không giới hạn số lượng theo tồn.
            if (isPendingRow(sizeRow)) {
                setRowQtyWarning(sizeRow, '');
                return true;
            }

            if (! selected) {
                setRowQtyWarning(sizeRow, '');
                return true;
            }

            const limit = stockLimit(selected.item);
            const value = Number(quantityInput.value || 0);
            quantityInput.max = limit;

            // Vượt tồn dự kiến: không tự cắt, mà báo rõ ngay tại dòng và chặn lưu.
            if (value > limit) {
                quantityInput.setCustomValidity(`Số lượng vượt tồn dự kiến (tối đa ${limit}).`);
                setRowQtyWarning(sizeRow, `⚠ Vượt tồn dự kiến — tối đa ${limit.toLocaleString('vi-VN')}.`);
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

            itemsContainer.querySelectorAll('.size-row').forEach(sizeRow => {
                sizeRow.querySelector('[data-product-id]').name = `items[${index}][product_id]`;
                sizeRow.querySelector('[data-quantity]').name = `items[${index}][quantity]`;
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

        function renderSuggestions(block, keyword = '') {
            const suggestions = block.querySelector('[data-suggestions]');
            const normalizedKeyword = keyword.trim().toLowerCase();
            const matches = productOptions.filter(group => {
                const hasKeyword = group.code.toLowerCase().includes(normalizedKeyword)
                    || group.name.toLowerCase().includes(normalizedKeyword);

                // Cho phép chọn lại mã đã có (thêm khối cùng mã với giá khác).
                const hasStock = group.items.some(product => stockLimit(product) > 0);

                return hasKeyword && hasStock;
            }).slice(0, 20);

            suggestions.innerHTML = '';

            if (matches.length === 0) {
                suggestions.style.display = 'none';
                return;
            }

            matches.forEach(group => {
                const button = document.createElement('button');
                const code = document.createElement('div');
                const name = document.createElement('div');
                const meta = document.createElement('div');

                button.type = 'button';
                button.className = 'product-suggestion';
                code.className = 'product-code';
                code.textContent = group.code;
                name.className = 'product-name';
                name.textContent = group.name;
                meta.className = 'product-meta';
                meta.textContent = `${group.items.filter(product => stockLimit(product) > 0).length}/${group.items.length} size còn tồn`;
                button.append(code, name, meta);
                button.addEventListener('click', () => selectProductCode(block, group.code));
                suggestions.appendChild(button);
            });

            suggestions.style.display = 'block';
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
                option.textContent = `${product.size} | Tồn dự kiến: ${stockLimit(product)} | Vải: ${product.fabric}`;
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
            updateComputedTotal();
        }

        function renderSelectedInfo(block) {
            const selectedInfo = block.querySelector('[data-selected-info]');
            const lines = Array.from(block.querySelectorAll('.size-row'))
                .map(sizeRow => findProductById(sizeRow.querySelector('[data-product-id]').value))
                .filter(Boolean)
                .map(selected => `${selected.group.code} - ${selected.item.name} | Size: ${selected.item.size} | Tồn dự kiến: ${stockLimit(selected.item)} | Vải: ${selected.item.fabric}`);

            selectedInfo.textContent = '';

            if (lines.length === 0) {
                selectedInfo.style.display = 'none';
                return;
            }

            lines.forEach((line, index) => {
                if (index > 0) {
                    selectedInfo.appendChild(document.createElement('br'));
                }

                selectedInfo.appendChild(document.createTextNode(line));
            });

            selectedInfo.style.display = 'block';
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
                    <input data-size-pending type="hidden" value="${size.size_pending ? '1' : '0'}">
                </div>
                <div class="field">
                    <label>Số lượng</label>
                    <input data-quantity type="number" min="1" step="1" value="${size.quantity || 1}" required>
                </div>
                <button class="button danger" data-remove-size type="button">Xóa size</button>
                <div class="field size-note-field">
                    <label>Ghi chú (size này)</label>
                    <input data-note type="text" maxlength="500" placeholder="VD: khách dặn giữ nếp, kèm phụ kiện...">
                </div>
                <div class="qty-warning" data-qty-warning style="display:none"></div>
            `;

            const sizeSelect = sizeRow.querySelector('[data-size]');
            const productIdInput = sizeRow.querySelector('[data-product-id]');
            const pendingInput = sizeRow.querySelector('[data-size-pending]');
            const quantityInput = sizeRow.querySelector('[data-quantity]');
            const noteInput = sizeRow.querySelector('[data-note]');
            noteInput.value = size.note != null ? size.note : '';

            // Rời ô số lượng: nếu vượt tồn dự kiến thì tự điều chỉnh về tối đa và báo.
            quantityInput.addEventListener('blur', () => {
                if (isPendingRow(sizeRow)) {
                    return;
                }

                const selected = findProductById(sizeRow.querySelector('[data-product-id]').value);

                if (! selected) {
                    return;
                }

                const limit = stockLimit(selected.item);
                const value = Number(quantityInput.value || 0);

                if (value > limit) {
                    quantityInput.value = limit;
                    updateQuantityLimit(sizeRow);
                    setRowQtyWarning(sizeRow, `Đã điều chỉnh về tối đa tồn dự kiến (${limit.toLocaleString('vi-VN')}).`);
                    updateComputedTotal();
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

        function addItemBlock(blockData = {}) {
            const block = document.createElement('div');
            block.className = 'order-item';
            block.dataset.index = itemIndex;
            const searchId = `product_search_${itemIndex}`;
            block.innerHTML = `
                <div class="field product-picker-cell">
                    <label for="${searchId}">Mã hàng</label>
                    <input id="${searchId}" data-search type="search" autocomplete="off" placeholder="Nhập mã hàng hoặc tên hàng..." required>
                    <div data-suggestions class="product-suggestions"></div>
                </div>
                <div data-size-rows class="size-rows"></div>
                <div class="rental-row">
                    <div class="field rental-field">
                        <label>Giá thuê (mã này)</label>
                        <input data-block-rental type="number" min="0" step="1000" value="0">
                    </div>
                    <div class="block-subtotal">Thành tiền: <span data-block-subtotal>0</span></div>
                </div>
                <div class="product-actions">
                    <button class="button secondary" data-add-size type="button" disabled>+ Thêm size</button>
                    <button class="button danger" data-remove-block type="button">Xóa mã hàng</button>
                </div>
                <div data-selected-info class="selected-product-info"></div>
                <div data-row-error class="error row-error"></div>
            `;
            itemIndex += 1;

            const searchInput = block.querySelector('[data-search]');
            const selectedInfo = block.querySelector('[data-selected-info]');

            block.querySelector('[data-block-rental]').addEventListener('input', updateComputedTotal);

            searchInput.addEventListener('input', () => {
                if (block.dataset.productCode) {
                    block.dataset.productCode = '';
                    clearSizeRows(block);
                    addSizeRow(block);
                }

                selectedInfo.style.display = 'none';
                clearRowError(block);
                updateAddSizeButton(block);
                renderSuggestions(block, searchInput.value);
            });

            searchInput.addEventListener('focus', () => renderSuggestions(block, searchInput.value));

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
            const rentalInput = block.querySelector('[data-block-rental]');
            if (blockData.rental != null && Number(blockData.rental) > 0) {
                rentalInput.value = Number(blockData.rental);
            } else if (block.dataset.productCode) {
                rentalInput.value = codeRentalPrice(block.dataset.productCode);
            }

            renderSelectedInfo(block);
            updateAddSizeButton(block);
            renumberRows();
            updateComputedTotal();

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

        addItemButton.addEventListener('click', () => addItemBlock());

        [pickupDateInput, eventDateInput, returnDateInput].forEach(input => {
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

        const groupedBlocks = [];
        const codeToBlockIndex = new Map();

        initialItems.forEach(item => {
            const selected = findProductById(item.product_id);
            const code = selected ? selected.group.code : null;
            const sizeData = { product_id: item.product_id, quantity: item.quantity, rental_price: item.rental_price, size_pending: item.size_pending, note: item.note };

            // Gộp theo MÃ + GIÁ THUÊ: cùng mã nhưng khác giá sẽ thành 2 khối riêng.
            const groupKey = code !== null ? code + '|' + (item.rental_price != null ? item.rental_price : '') : null;

            if (groupKey !== null && codeToBlockIndex.has(groupKey)) {
                groupedBlocks[codeToBlockIndex.get(groupKey)].sizes.push(sizeData);
                return;
            }

            if (groupKey !== null) {
                codeToBlockIndex.set(groupKey, groupedBlocks.length);
            }

            // Giá thuê của mã lấy từ dòng đầu tiên (các size cùng mã dùng chung giá).
            groupedBlocks.push({ code, sizes: [sizeData], rental: item.rental_price });
        });

        if (groupedBlocks.length > 0) {
            groupedBlocks.forEach(blockData => addItemBlock(blockData));
        } else {
            addItemBlock();
        }

        itemsContainer.addEventListener('input', event => {
            if (event.target.matches('[data-quantity]')) {
                updateComputedTotal();
            }
        });

        resetAvailabilityToCurrentStock();
        refreshAvailability();
        updateComputedTotal();
    </script>
</body>
</html>
