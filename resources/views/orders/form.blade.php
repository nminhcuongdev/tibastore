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
        .item-buttons {
            align-items: stretch;
            display: flex;
            flex-direction: column;
            gap: 8px;
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
                <label>Trạng thái</label>
                <div class="readonly-value">{{ $order->statusLabel() }}</div>
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

        async function refreshAvailability() {
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
                itemsContainer.querySelectorAll('.order-item').forEach(validateRow);
            } catch (error) {
                if (requestId !== availabilityRequestId) {
                    return;
                }

                resetAvailabilityToCurrentStock();
                refreshProductChoices();
            }
        }

        function selectedProductIds(exceptRow = null) {
            return new Set(Array.from(itemsContainer.querySelectorAll('.order-item'))
                .filter(row => row !== exceptRow)
                .map(row => row.querySelector('[data-product-id]').value)
                .filter(Boolean)
                .map(String));
        }

        function availableProductsForRow(group, row, selectedProductId = null) {
            const selectedIds = selectedProductIds(row);

            return group.items.filter(product => {
                const isCurrentSelection = selectedProductId && Number(product.id) === Number(selectedProductId);

                return isCurrentSelection || (! selectedIds.has(String(product.id)) && stockLimit(product) > 0);
            });
        }

        function availableProductsForNewSize(code) {
            const group = productOptions.find(item => item.code === code);

            if (! group) {
                return [];
            }

            const selectedIds = selectedProductIds();

            return group.items.filter(product => ! selectedIds.has(String(product.id)) && stockLimit(product) > 0);
        }

        function updateAddSizeButton(row) {
            const addSizeButton = row.querySelector('[data-add-size]');

            if (! addSizeButton) {
                return;
            }

            addSizeButton.disabled = ! row.dataset.productCode
                || ! row.querySelector('[data-product-id]').value
                || availableProductsForNewSize(row.dataset.productCode).length === 0;
        }

        function updateAllAddSizeButtons() {
            itemsContainer.querySelectorAll('.order-item').forEach(updateAddSizeButton);
        }

        function updateQuantityLimit(row) {
            const quantityInput = row.querySelector('[data-quantity]');
            const productId = row.querySelector('[data-product-id]').value;
            const selected = findProductById(productId);

            quantityInput.setCustomValidity('');
            quantityInput.removeAttribute('max');

            if (! selected) {
                return true;
            }

            const limit = stockLimit(selected.item);
            quantityInput.max = limit;

            if (limit > 0 && Number(quantityInput.value || 0) > limit) {
                quantityInput.value = limit;
            }

            if (Number(quantityInput.value || 0) < 1 || Number(quantityInput.value || 0) > limit) {
                quantityInput.setCustomValidity(`Số lượng không được vượt quá tồn dự kiến (${limit}).`);
                return false;
            }

            return true;
        }

        function validateRow(row) {
            const rowError = row.querySelector('[data-row-error]');
            const productId = row.querySelector('[data-product-id]').value;
            rowError.style.display = 'none';
            rowError.textContent = '';

            if (! productId) {
                return false;
            }

            if (! updateQuantityLimit(row)) {
                const max = row.querySelector('[data-quantity]').max;
                rowError.style.display = 'block';
                rowError.textContent = `Số lượng không được vượt quá tồn dự kiến (${max}).`;
                return false;
            }

            return true;
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
                const hasKeyword = group.code.toLowerCase().includes(normalizedKeyword)
                    || group.name.toLowerCase().includes(normalizedKeyword);

                return hasKeyword && availableProductsForRow(group, row).length > 0;
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
                meta.textContent = `${availableProductsForRow(group, row).length}/${group.items.length} size có thể chọn`;
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
            row.dataset.productCode = group ? group.code : '';
            suggestions.style.display = 'none';
            productIdInput.value = '';
            sizeSelect.innerHTML = '<option value="">Chọn size</option>';
            sizeSelect.disabled = !group;
            selectedInfo.style.display = 'none';
            selectedInfo.textContent = '';

            if (!group) {
                updateAddSizeButton(row);
                return;
            }

            const availableProducts = availableProductsForRow(group, row, selectedProductId);

            availableProducts.forEach(product => {
                const option = document.createElement('option');
                option.value = product.id;
                option.textContent = `${product.size} | Tồn dự kiến: ${stockLimit(product)} | Vải: ${product.fabric}`;
                option.selected = Number(product.id) === Number(selectedProductId);
                sizeSelect.appendChild(option);
            });

            if (availableProducts.length === 0) {
                const option = document.createElement('option');
                option.textContent = 'Mã hàng này không còn size có thể chọn';
                option.disabled = true;
                sizeSelect.appendChild(option);
            }

            if (selectedProductId && availableProducts.some(product => Number(product.id) === Number(selectedProductId))) {
                productIdInput.value = selectedProductId;
                renderSelectedInfo(row);
                updateQuantityLimit(row);
            }

            updateAddSizeButton(row);
        }

        function renderSelectedInfo(row) {
            const productIdInput = row.querySelector('[data-product-id]');
            const selectedInfo = row.querySelector('[data-selected-info]');
            const selected = findProductById(productIdInput.value);

            if (!selected) {
                selectedInfo.style.display = 'none';
                updateAddSizeButton(row);
                selectedInfo.textContent = '';
                return;
            }

            selectedInfo.style.display = 'block';
            selectedInfo.textContent = `${selected.group.code} - ${selected.item.name} | Size: ${selected.item.size} | Tồn dự kiến: ${stockLimit(selected.item)} | Vải: ${selected.item.fabric}`;
        }

        function refreshProductChoices() {
            itemsContainer.querySelectorAll('.order-item').forEach(row => {
                if (! row.dataset.productCode) {
                    return;
                }

                selectProductCode(row, row.dataset.productCode, row.querySelector('[data-product-id]').value);
            });
            updateAllAddSizeButtons();
        }

        function addSizeForRow(row) {
            const code = row.dataset.productCode;
            const selectedInfo = row.querySelector('[data-selected-info]');

            if (! code) {
                selectedInfo.style.display = 'block';
                selectedInfo.textContent = 'Vui lòng chọn mã hàng trước khi thêm size.';
                return;
            }

            if (availableProductsForNewSize(code).length === 0) {
                selectedInfo.style.display = 'block';
                selectedInfo.textContent = 'Mã hàng này không còn size khác có thể chọn.';
                updateAddSizeButton(row);
                return;
            }

            const newRow = addItemRow({ product_code: code });
            newRow.querySelector('[data-size]').focus();
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
                <div class="field item-buttons">
                    <label>&nbsp;</label>
                    <button class="button secondary" data-add-size type="button" disabled>Thêm size</button>
                    <button class="button danger" data-remove type="button">Xóa</button>
                </div>
                <div data-selected-info class="selected-product-info"></div>
                <div data-row-error class="error row-error"></div>
            `;

            const searchInput = row.querySelector('[data-search]');
            const sizeSelect = row.querySelector('[data-size]');
            const productIdInput = row.querySelector('[data-product-id]');
            const selectedInfo = row.querySelector('[data-selected-info]');
            const quantityInput = row.querySelector('[data-quantity]');
            const addSizeButton = row.querySelector('[data-add-size]');

            searchInput.addEventListener('input', () => {
                productIdInput.value = '';
                row.dataset.productCode = '';
                sizeSelect.innerHTML = '<option value="">Chọn size</option>';
                sizeSelect.disabled = true;
                selectedInfo.style.display = 'none';
                updateAddSizeButton(row);
                renderSuggestions(row, searchInput.value);
            });

            searchInput.addEventListener('focus', () => renderSuggestions(row, searchInput.value));

            sizeSelect.addEventListener('change', () => {
                if (sizeSelect.value && selectedProductIds(row).has(String(sizeSelect.value))) {
                    productIdInput.value = '';
                    sizeSelect.value = '';
                    row.querySelector('[data-row-error]').style.display = 'block';
                    row.querySelector('[data-row-error]').textContent = 'Mã hàng và size này đã được chọn trong đơn.';
                    refreshProductChoices();
                    return;
                }

                productIdInput.value = sizeSelect.value;
                renderSelectedInfo(row);
                updateQuantityLimit(row);
                refreshProductChoices();
            });

            quantityInput.addEventListener('input', () => {
                updateQuantityLimit(row);
            });

            addSizeButton.addEventListener('click', () => addSizeForRow(row));

            row.querySelector('[data-remove]').addEventListener('click', () => {
                if (itemsContainer.querySelectorAll('.order-item').length === 1) {
                    selectedInfo.style.display = 'block';
                    selectedInfo.textContent = 'Đơn hàng cần ít nhất một sản phẩm.';
                    return;
                }

                row.remove();
                renumberRows();
                refreshProductChoices();
            });

            itemsContainer.appendChild(row);
            itemIndex += 1;

            if (item.product_id) {
                const selected = findProductById(item.product_id);

                if (selected) {
                    selectProductCode(row, selected.group.code, item.product_id);
                }
            } else if (item.product_code) {
                selectProductCode(row, item.product_code);
            }

            renumberRows();
            updateAllAddSizeButtons();

            return row;
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

        [pickupDateInput, eventDateInput, returnDateInput].forEach(input => {
            input.addEventListener('change', refreshAvailability);
        });

        orderForm.addEventListener('submit', event => {
            const rows = Array.from(itemsContainer.querySelectorAll('.order-item'));
            const invalidRow = rows.find(row => !row.querySelector('[data-product-id]').value);
            const seenProductIds = new Set();
            const duplicateRow = rows.find(row => {
                const productId = row.querySelector('[data-product-id]').value;

                if (! productId) {
                    return false;
                }

                if (seenProductIds.has(productId)) {
                    return true;
                }

                seenProductIds.add(productId);
                return false;
            });
            const invalidQuantityRow = rows.find(row => row.querySelector('[data-product-id]').value && ! validateRow(row));

            if (!invalidRow) {
                if (duplicateRow) {
                    event.preventDefault();
                    duplicateRow.querySelector('[data-row-error]').style.display = 'block';
                    duplicateRow.querySelector('[data-row-error]').textContent = 'Mã hàng và size này đã được chọn trong đơn.';
                    duplicateRow.querySelector('[data-size]').focus();
                    return;
                }

                if (invalidQuantityRow) {
                    event.preventDefault();
                    invalidQuantityRow.querySelector('[data-quantity]').focus();
                }

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

        resetAvailabilityToCurrentStock();
        refreshAvailability();
    </script>
</body>
</html>
