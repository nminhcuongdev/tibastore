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
            display: grid;
            gap: 12px;
        }
        .order-item {
            background: #fffafb;
            border: 1px solid #f2d3dc;
            border-radius: 8px;
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(0, 1fr) 220px 120px auto;
            padding: 14px;
            position: relative;
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
        @media (max-width: 860px) {
            .topbar,
            .form-shell,
            .order-item {
                align-items: stretch;
                grid-template-columns: 1fr;
            }
            .topbar { flex-direction: column; }
            .actions { justify-content: stretch; }
            .button { justify-content: center; width: 100%; }
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

        @php
            $oldItems = old('items');
            $initialItems = $oldItems !== null
                ? collect($oldItems)->map(fn ($item) => [
                    'product_id' => $item['product_id'] ?? '',
                    'quantity' => $item['quantity'] ?? 1,
                ])->values()
                : ($orderItems->isNotEmpty()
                    ? $orderItems->map(fn ($item) => [
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                    ])->values()
                    : collect([[
                        'product_id' => $order->product_id,
                        'quantity' => $order->quantity ?? 1,
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
                        <option value="{{ $value }}" @selected(old('status', $order->status ?? 'len_don') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('status') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field full">
                <label>Sản phẩm trong đơn</label>
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
    @include('orders.reminders-popup')
    <script>
        const productOptions = @json($productOptions, JSON_UNESCAPED_UNICODE);
        const initialItems = @json($initialItems->values(), JSON_UNESCAPED_UNICODE);
        const itemsContainer = document.getElementById('order_items');
        const addItemButton = document.getElementById('add_item_button');
        const orderForm = document.getElementById('order_form');
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

        function renumberRows() {
            itemsContainer.querySelectorAll('.order-item').forEach((row, index) => {
                row.querySelector('[data-product-id]').name = `items[${index}][product_id]`;
                row.querySelector('[data-quantity]').name = `items[${index}][quantity]`;
            });
        }

        function renderSuggestions(row, keyword = '') {
            const suggestions = row.querySelector('[data-suggestions]');
            const normalizedKeyword = keyword.trim().toLowerCase();
            const matches = productOptions.filter(group => {
                return group.code.toLowerCase().includes(normalizedKeyword)
                    || group.name.toLowerCase().includes(normalizedKeyword);
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
                meta.textContent = `${group.items.length} size đang có`;
                button.append(code, name, meta);
                button.addEventListener('click', () => selectProductCode(row, group.code));
                suggestions.appendChild(button);
            });

            suggestions.style.display = 'block';
        }

        function selectProductCode(row, code, selectedProductId = null) {
            const group = productOptions.find(item => item.code === code);
            const searchInput = row.querySelector('[data-search]');
            const sizeSelect = row.querySelector('[data-size]');
            const productIdInput = row.querySelector('[data-product-id]');
            const suggestions = row.querySelector('[data-suggestions]');
            const selectedInfo = row.querySelector('[data-selected-info]');

            searchInput.value = group ? `${group.code} - ${group.name}` : code;
            suggestions.style.display = 'none';
            productIdInput.value = '';
            sizeSelect.innerHTML = '<option value="">Chọn size</option>';
            sizeSelect.disabled = !group;
            selectedInfo.style.display = 'none';
            selectedInfo.textContent = '';

            if (!group) {
                return;
            }

            group.items.forEach(product => {
                const option = document.createElement('option');
                option.value = product.id;
                option.textContent = `${product.size} | Tồn: ${product.stock_quantity} | Vải: ${product.fabric}`;
                option.selected = Number(product.id) === Number(selectedProductId);
                sizeSelect.appendChild(option);
            });

            if (selectedProductId) {
                productIdInput.value = selectedProductId;
                renderSelectedInfo(row);
            }
        }

        function renderSelectedInfo(row) {
            const productIdInput = row.querySelector('[data-product-id]');
            const selectedInfo = row.querySelector('[data-selected-info]');
            const selected = findProductById(productIdInput.value);

            if (!selected) {
                selectedInfo.style.display = 'none';
                selectedInfo.textContent = '';
                return;
            }

            selectedInfo.style.display = 'block';
            selectedInfo.textContent = `${selected.group.code} - ${selected.item.name} | Size: ${selected.item.size} | Tồn: ${selected.item.stock_quantity} | Vải: ${selected.item.fabric}`;
        }

        function addItemRow(item = {}) {
            const row = document.createElement('div');
            row.className = 'order-item';
            row.dataset.index = itemIndex;
            row.innerHTML = `
                <div class="field product-picker-cell">
                    <label for="product_search_${itemIndex}">Mã hàng</label>
                    <input id="product_search_${itemIndex}" data-search type="search" autocomplete="off" placeholder="Nhập mã hàng hoặc tên hàng..." required>
                    <div data-suggestions class="product-suggestions"></div>
                </div>
                <div class="field">
                    <label for="product_size_${itemIndex}">Size</label>
                    <select id="product_size_${itemIndex}" data-size required disabled>
                        <option value="">Chọn size</option>
                    </select>
                    <input data-product-id type="hidden" value="${item.product_id || ''}" required>
                </div>
                <div class="field">
                    <label for="quantity_${itemIndex}">Số lượng</label>
                    <input id="quantity_${itemIndex}" data-quantity type="number" min="1" step="1" value="${item.quantity || 1}" required>
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <button class="button danger" data-remove type="button">Xóa</button>
                </div>
                <div data-selected-info class="selected-product-info"></div>
            `;

            const searchInput = row.querySelector('[data-search]');
            const sizeSelect = row.querySelector('[data-size]');
            const productIdInput = row.querySelector('[data-product-id]');
            const selectedInfo = row.querySelector('[data-selected-info]');

            searchInput.addEventListener('input', () => {
                productIdInput.value = '';
                sizeSelect.innerHTML = '<option value="">Chọn size</option>';
                sizeSelect.disabled = true;
                selectedInfo.style.display = 'none';
                renderSuggestions(row, searchInput.value);
            });

            searchInput.addEventListener('focus', () => renderSuggestions(row, searchInput.value));

            sizeSelect.addEventListener('change', () => {
                productIdInput.value = sizeSelect.value;
                renderSelectedInfo(row);
            });

            row.querySelector('[data-remove]').addEventListener('click', () => {
                if (itemsContainer.querySelectorAll('.order-item').length === 1) {
                    selectedInfo.style.display = 'block';
                    selectedInfo.textContent = 'Đơn hàng cần ít nhất một sản phẩm.';
                    return;
                }

                row.remove();
                renumberRows();
            });

            itemsContainer.appendChild(row);
            itemIndex += 1;

            if (item.product_id) {
                const selected = findProductById(item.product_id);

                if (selected) {
                    selectProductCode(row, selected.group.code, item.product_id);
                }
            }

            renumberRows();
        }

        document.addEventListener('click', event => {
            itemsContainer.querySelectorAll('[data-suggestions]').forEach(suggestions => {
                const row = suggestions.closest('.order-item');

                if (!row.contains(event.target)) {
                    suggestions.style.display = 'none';
                }
            });
        });

        addItemButton.addEventListener('click', () => addItemRow());

        orderForm.addEventListener('submit', event => {
            const rows = Array.from(itemsContainer.querySelectorAll('.order-item'));
            const invalidRow = rows.find(row => !row.querySelector('[data-product-id]').value);

            if (!invalidRow) {
                return;
            }

            event.preventDefault();
            const searchInput = invalidRow.querySelector('[data-search]');
            const selectedInfo = invalidRow.querySelector('[data-selected-info]');
            searchInput.focus();
            selectedInfo.style.display = 'block';
            selectedInfo.textContent = 'Vui lòng chọn mã hàng và size trước khi lưu đơn.';
        });

        if (initialItems.length > 0) {
            initialItems.forEach(item => addItemRow(item));
        } else {
            addItemRow();
        }
    </script>
</body>
</html>
