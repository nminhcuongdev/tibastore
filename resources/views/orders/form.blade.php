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
            grid-template-columns: 70px 220px 120px auto;
        }
        .size-row .button.danger {
            align-self: end;
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
        @media (max-width: 860px) {
            .topbar,
            .form-shell,
            .size-row {
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
                        <option value="{{ $value }}" @selected(old('status', $order->status ?? \App\Models\Order::DEFAULT_STATUS) === $value)>{{ $label }}</option>
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
                itemsContainer.querySelectorAll('.size-row').forEach(validateSizeRow);
            } catch (error) {
                if (requestId !== availabilityRequestId) {
                    return;
                }

                resetAvailabilityToCurrentStock();
                refreshProductChoices();
            }
        }

        function selectedProductIds(exceptSizeRow = null) {
            return new Set(Array.from(itemsContainer.querySelectorAll('.size-row'))
                .filter(sizeRow => sizeRow !== exceptSizeRow)
                .map(sizeRow => sizeRow.querySelector('[data-product-id]').value)
                .filter(Boolean)
                .map(String));
        }

        function availableProductsForSizeRow(group, sizeRow, selectedProductId = null) {
            const selectedIds = selectedProductIds(sizeRow);

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

        function updateAddSizeButton(block) {
            const addSizeButton = block.querySelector('[data-add-size]');

            if (! addSizeButton) {
                return;
            }

            addSizeButton.disabled = ! block.dataset.productCode
                || availableProductsForNewSize(block.dataset.productCode).length === 0;
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

        function updateQuantityLimit(sizeRow) {
            const quantityInput = sizeRow.querySelector('[data-quantity]');
            const productId = sizeRow.querySelector('[data-product-id]').value;
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
                index += 1;
            });
        }

        function renderSuggestions(block, keyword = '') {
            const suggestions = block.querySelector('[data-suggestions]');
            const normalizedKeyword = keyword.trim().toLowerCase();
            const matches = productOptions.filter(group => {
                const hasKeyword = group.code.toLowerCase().includes(normalizedKeyword)
                    || group.name.toLowerCase().includes(normalizedKeyword);

                return hasKeyword && availableProductsForNewSize(group.code).length > 0;
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
                meta.textContent = `${availableProductsForNewSize(group.code).length}/${group.items.length} size có thể chọn`;
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
                option.selected = Number(product.id) === Number(currentSelection);
                sizeSelect.appendChild(option);
            });

            if (availableProducts.length === 0) {
                const option = document.createElement('option');
                option.textContent = 'Mã hàng này không còn size có thể chọn';
                option.disabled = true;
                sizeSelect.appendChild(option);
            }

            if (currentSelection && availableProducts.some(product => Number(product.id) === Number(currentSelection))) {
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
            const imageUrl = selected && selected.item.image_url ? selected.item.image_url : null;

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

            updateAddSizeButton(block);
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
        }

        function addSizeForBlock(block) {
            const code = block.dataset.productCode;

            if (! code) {
                showBlockInfo(block, 'Vui lòng chọn mã hàng trước khi thêm size.');
                return;
            }

            if (availableProductsForNewSize(code).length === 0) {
                showBlockInfo(block, 'Mã hàng này không còn size khác có thể chọn.');
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
                </div>
                <div class="field">
                    <label>Số lượng</label>
                    <input data-quantity type="number" min="1" step="1" value="${size.quantity || 1}" required>
                </div>
                <button class="button danger" data-remove-size type="button">Xóa size</button>
            `;

            const sizeSelect = sizeRow.querySelector('[data-size]');
            const productIdInput = sizeRow.querySelector('[data-product-id]');
            const quantityInput = sizeRow.querySelector('[data-quantity]');

            sizeSelect.addEventListener('change', () => {
                if (sizeSelect.value && selectedProductIds(sizeRow).has(String(sizeSelect.value))) {
                    productIdInput.value = '';
                    sizeSelect.value = '';
                    showRowError(block, 'Mã hàng và size này đã được chọn trong đơn.');
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

            renderSelectedInfo(block);
            updateAddSizeButton(block);
            renumberRows();

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
            input.addEventListener('change', refreshAvailability);
        });

        orderForm.addEventListener('submit', event => {
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

            const seenProductIds = new Set();
            const duplicateRow = sizeRows.find(sizeRow => {
                const productId = sizeRow.querySelector('[data-product-id]').value;

                if (seenProductIds.has(productId)) {
                    return true;
                }

                seenProductIds.add(productId);
                return false;
            });

            if (duplicateRow) {
                event.preventDefault();
                const block = duplicateRow.closest('.order-item');
                showRowError(block, 'Mã hàng và size này đã được chọn trong đơn.');
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
            const sizeData = { product_id: item.product_id, quantity: item.quantity };

            if (code && codeToBlockIndex.has(code)) {
                groupedBlocks[codeToBlockIndex.get(code)].sizes.push(sizeData);
                return;
            }

            if (code) {
                codeToBlockIndex.set(code, groupedBlocks.length);
            }

            groupedBlocks.push({ code, sizes: [sizeData] });
        });

        if (groupedBlocks.length > 0) {
            groupedBlocks.forEach(blockData => addItemBlock(blockData));
        } else {
            addItemBlock();
        }

        resetAvailabilityToCurrentStock();
        refreshAvailability();
    </script>
</body>
</html>
